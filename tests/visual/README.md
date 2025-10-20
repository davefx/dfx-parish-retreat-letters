# Visual Print Layout Tests

This directory contains HTML files that can be opened in a browser to visually test print layouts.

## print-layout-test.html

Tests the fix for issue #109: "First image in multi-file messages doesn't fit in first page"

### How to Use

1. Open `print-layout-test.html` in a web browser (Chrome, Firefox, Safari, or Edge)
2. Press `Ctrl+P` (Windows/Linux) or `Cmd+P` (Mac) to open Print Preview
3. Verify the layout matches expectations

### Expected Results

- **Page 1**: Should contain both the "To:/From:" header AND the first blue image
- **Page 2**: Should contain only the second green image
- **Page 3**: Should contain only the third pink image

### What the Test Validates

The test validates that after adding the "To:" field to the print header:

1. The first image container uses `min-height: 0` (not `min-height: 100vh`) to allow natural sizing
2. The first image container has `max-height: calc(100vh - 200px)` to fit below the header
3. The first image element has `max-height: calc(100vh - 150px)` for proper scaling
4. Each subsequent image gets its own page with full viewport height

### CSS Being Tested

```css
@media print {
    /* Base class for all multi-image containers */
    .file-content.multi-image {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;  /* All images default to full page */
        page-break-after: always;
        page-break-inside: avoid;  /* Don't break container across pages */
    }
    
    /* Override for first image to fit with header */
    .file-content.multi-image:first-child {
        display: block;  /* Override flex - simpler layout for first image */
        min-height: 0;  /* Allow natural sizing */
        max-height: calc(100vh - 200px);  /* Constrain to fit below header */
        page-break-before: avoid;  /* Keep with header above */
        page-break-inside: avoid;  /* Don't break the container */
    }
    
    .file-content.multi-image:first-child .file-image {
        max-height: calc(100vh - 150px);  /* Image constrained within container */
    }
}
```

### Critical Fixes

1. **display: block** - The first image uses `display: block` instead of `display: flex`. This simplifies the layout and prevents flex-related issues with centering that could cause unexpected height calculations.

2. **page-break-before: avoid** - Keeps the first image container with the header above it, preventing a break between the header and the image.

3. **page-break-inside: avoid** - Ensures the image container itself isn't broken across pages (this is correct - we never want to break an image).

4. **min-height: 0** - Allows the container to size naturally based on content, overriding the base class's `min-height: 100vh`.

5. **max-height: calc(100vh - 200px)** - Constrains the container to fit within the remaining page space after the header.

### Troubleshooting

If the first image still appears on page 2:

1. Check that your browser supports CSS calc() in print media queries
2. Try a different browser (Chrome usually has the best print preview)
3. Check the browser console for CSS errors
4. Ensure print margins are set to default/minimal in print settings

### Browser Compatibility

Tested and working in:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Manual Testing vs Automated Testing

This is a **manual visual test** because:

1. Print layout behavior varies by browser and printer driver
2. CSS `@media print` queries require actual print preview/rendering
3. Automated tools like Playwright can capture print CSS but may not perfectly replicate browser print engines
4. Visual confirmation by a human is more reliable for layout issues

For automated testing, we have unit tests that verify the CSS values are present in the generated HTML.
