/**
 * Client-side PDF header builder.
 *
 * Reads window.DFXPRL_PRINT_DATA (set by the print page) and uses pdf-lib
 * to add a From/To header strip on page 1 of the original PDF, preserving
 * later pages as-is. Falls back to serving the raw PDF if anything fails.
 *
 * @package DFXPRL
 * @since   26.05.19
 */
(function () {
	'use strict';

	var printTime = 0;

	function base64ToBytes( b64 ) {
		var bin = atob( b64 );
		var bytes = new Uint8Array( bin.length );
		for ( var i = 0; i < bin.length; i++ ) {
			bytes[ i ] = bin.charCodeAt( i );
		}
		return bytes;
	}

	function getFrame() {
		return document.getElementById( 'dfxprl-pdf-frame' );
	}

	function setFrameSrc( src ) {
		var frame = getFrame();
		if ( frame ) {
			frame.src = src;
		}
	}

	function debugLog( where ) {
		if ( window.console && window.console.log ) {
			window.console.log( 'DFXPRL print signal:', where, '(printTime=' + printTime + ')' );
		}
	}

	function closeIfOurPrint( where ) {
		debugLog( 'closeIfOurPrint via ' + where );
		if ( printTime > 0 && ( Date.now() - printTime ) > 300 ) {
			debugLog( 'calling window.close()' );
			window.close();
		}
	}

	function installAutoCloseListeners() {
		window.addEventListener( 'afterprint', function () {
			setTimeout( function () { closeIfOurPrint( 'parent.afterprint' ); }, 100 );
		} );
		window.addEventListener( 'focus', function () {
			if ( printTime > 0 ) {
				setTimeout( function () { closeIfOurPrint( 'parent.focus' ); }, 500 );
			}
		} );
		document.addEventListener( 'visibilitychange', function () {
			if ( document.visibilityState === 'visible' && printTime > 0 ) {
				setTimeout( function () { closeIfOurPrint( 'parent.visibilitychange' ); }, 500 );
			}
		} );
		if ( window.matchMedia ) {
			var mql = window.matchMedia( 'print' );
			var onChange = function ( e ) {
				if ( ! e.matches ) {
					setTimeout( function () { closeIfOurPrint( 'parent.matchMedia(print)' ); }, 100 );
				}
			};
			if ( mql.addEventListener ) {
				mql.addEventListener( 'change', onChange );
			} else if ( mql.addListener ) {
				mql.addListener( onChange );
			}
		}
	}

	function triggerPrint() {
		printTime = Date.now();
		debugLog( 'calling iframe.contentWindow.print()' );
		var frame = getFrame();
		try {
			// Don't focus the iframe — that prevents the parent's focus event
			// from firing when the print dialog returns control to the page.
			frame.contentWindow.print();
		} catch ( e ) {
			debugLog( 'iframe.print() threw, falling back to window.print(): ' + e );
			window.print();
		}
	}

	async function buildAugmentedPdf( data ) {
		if ( ! window.PDFLib ) {
			throw new Error( 'pdf-lib not loaded' );
		}
		var PDFLib = window.PDFLib;
		var PDFDocument = PDFLib.PDFDocument;
		var StandardFonts = PDFLib.StandardFonts;
		var rgb = PDFLib.rgb;

		var srcBytes = base64ToBytes( data.pdfBase64 );

		// Load src to read metadata and embed page 1 as a Form XObject.
		var srcDoc = await PDFDocument.load( srcBytes, { ignoreEncryption: true } );
		var pageCount = srcDoc.getPageCount();
		if ( pageCount < 1 ) {
			throw new Error( 'PDF has no pages' );
		}

		var outDoc = await PDFDocument.create();
		var font = await outDoc.embedFont( StandardFonts.HelveticaBold );

		// Embed page 1 of the source as a Form XObject we can scale.
		var embedded = await outDoc.embedPdf( srcDoc, [ 0 ] );
		var embeddedFirst = embedded[ 0 ];

		var firstSize = srcDoc.getPage( 0 ).getSize();
		var pageWidth = firstSize.width;
		var pageHeight = firstSize.height;

		// Layout constants in PDF points (1 mm ≈ 2.834 pt).
		// Mirrors the previous server-side TCPDF layout: ~16 mm header strip.
		var HEADER_HEIGHT = 45;
		var HEADER_MARGIN = 11;
		var LINE_HEIGHT = 14;
		var BOTTOM_MARGIN = 28;
		var FONT_SIZE = 10;

		var availableHeight = pageHeight - HEADER_HEIGHT - BOTTOM_MARGIN;
		var scale = availableHeight / pageHeight;
		var scaledWidth = pageWidth * scale;
		var scaledHeight = pageHeight * scale;
		var xOffset = ( pageWidth - scaledWidth ) / 2;

		var newFirstPage = outDoc.addPage( [ pageWidth, pageHeight ] );

		// Align "To:" and "From:" labels into a fixed-width column.
		var toLabelText = data.toLabel + ': ';
		var fromLabelText = data.fromLabel + ': ';
		var toLabelWidth = font.widthOfTextAtSize( toLabelText, FONT_SIZE );
		var fromLabelWidth = font.widthOfTextAtSize( fromLabelText, FONT_SIZE );
		var labelWidth = Math.max( toLabelWidth, fromLabelWidth ) + 3;

		// PDF coordinates: origin at bottom-left. Header sits near the top.
		var yPos = pageHeight - HEADER_MARGIN - FONT_SIZE;
		var textColor = rgb( 0.2, 0.2, 0.2 );

		if ( data.to ) {
			newFirstPage.drawText( toLabelText, { x: HEADER_MARGIN, y: yPos, size: FONT_SIZE, font: font, color: textColor } );
			newFirstPage.drawText( data.to, { x: HEADER_MARGIN + labelWidth, y: yPos, size: FONT_SIZE, font: font, color: textColor } );
			yPos -= LINE_HEIGHT;
		}
		if ( data.from ) {
			newFirstPage.drawText( fromLabelText, { x: HEADER_MARGIN, y: yPos, size: FONT_SIZE, font: font, color: textColor } );
			newFirstPage.drawText( data.from, { x: HEADER_MARGIN + labelWidth, y: yPos, size: FONT_SIZE, font: font, color: textColor } );
		}

		// Draw the scaled-down first page below the header.
		newFirstPage.drawPage( embeddedFirst, {
			x: xOffset,
			y: BOTTOM_MARGIN,
			width: scaledWidth,
			height: scaledHeight,
		} );

		// Subtle border around the scaled page so it's clearly demarcated.
		newFirstPage.drawRectangle( {
			x: xOffset,
			y: BOTTOM_MARGIN,
			width: scaledWidth,
			height: scaledHeight,
			borderColor: rgb( 0.7, 0.7, 0.7 ),
			borderWidth: 0.5,
		} );

		// Copy remaining pages unchanged.
		//
		// Reload the source bytes into a fresh PDFDocument before calling
		// copyPages: mixing embedPdf() and copyPages() on the same source
		// PDFDocument has been observed to silently drop pages.
		if ( pageCount > 1 ) {
			var srcDocForCopy = await PDFDocument.load( srcBytes, { ignoreEncryption: true } );
			var indices = [];
			for ( var i = 1; i < pageCount; i++ ) {
				indices.push( i );
			}
			var copied = await outDoc.copyPages( srcDocForCopy, indices );
			copied.forEach( function ( p ) {
				outDoc.addPage( p );
			} );
		}

		if ( window.console && window.console.log ) {
			window.console.log(
				'DFXPRL: built PDF with',
				outDoc.getPageCount(),
				'page(s) from a source of',
				pageCount,
				'page(s)'
			);
		}

		var outBytes = await outDoc.save();
		return new Blob( [ outBytes ], { type: 'application/pdf' } );
	}

	function run() {
		var data = window.DFXPRL_PRINT_DATA;
		if ( ! data || ! data.pdfBase64 ) {
			return;
		}
		installAutoCloseListeners();

		function loadFrameAndPrint( blob ) {
			var frame = getFrame();
			if ( ! frame ) {
				return;
			}
			frame.addEventListener( 'load', function onFrameLoad() {
				frame.removeEventListener( 'load', onFrameLoad );
				debugLog( 'iframe loaded' );
				// The print dialog is triggered on the iframe's window, so its
				// afterprint event fires there — not on the parent. Listen on both.
				try {
					if ( frame.contentWindow ) {
						frame.contentWindow.addEventListener( 'afterprint', function () {
							setTimeout( function () { closeIfOurPrint( 'iframe.afterprint' ); }, 100 );
						} );
						if ( frame.contentWindow.matchMedia ) {
							var iframeMql = frame.contentWindow.matchMedia( 'print' );
							var onIframeChange = function ( e ) {
								if ( ! e.matches ) {
									setTimeout( function () { closeIfOurPrint( 'iframe.matchMedia(print)' ); }, 100 );
								}
							};
							if ( iframeMql.addEventListener ) {
								iframeMql.addEventListener( 'change', onIframeChange );
							} else if ( iframeMql.addListener ) {
								iframeMql.addListener( onIframeChange );
							}
						}
					}
				} catch ( e ) {
					debugLog( 'iframe listener registration threw: ' + e );
				}
				// Give the in-frame PDF viewer a moment to initialise its UI.
				setTimeout( triggerPrint, 800 );
			} );
			frame.src = URL.createObjectURL( blob );
		}

		buildAugmentedPdf( data )
			.then( loadFrameAndPrint )
			.catch( function ( err ) {
				if ( window.console && window.console.warn ) {
					window.console.warn( 'DFXPRL: client-side PDF header failed, falling back to raw PDF.', err );
				}
				var bytes = base64ToBytes( data.pdfBase64 );
				loadFrameAndPrint( new Blob( [ bytes ], { type: 'application/pdf' } ) );
			} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', run );
	} else {
		run();
	}
} )();
