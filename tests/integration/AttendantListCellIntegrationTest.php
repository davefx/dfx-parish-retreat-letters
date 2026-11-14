<?php
/**
 * Cells of the attendants list, as the mobile cards and the desktop pane use them.
 *
 * @package DFXPRL
 */

/**
 * Renders list cells against a real WordPress install.
 */
class AttendantListCellIntegrationTest extends WP_UnitTestCase {

	private function cell( $key, $attendant, $message_count = 0 ) {
		$admin  = DFXPRL_Admin::get_instance();
		$method = new ReflectionMethod( $admin, 'render_attendant_list_cell' );
		$retreat = (object) array( 'id' => 77, 'message_request_template' => '' );
		return $method->invoke( $admin, $key, $retreat, $attendant, $message_count, 0, array(), array() );
	}

	private function attendant( $overrides = array() ) {
		return (object) array_merge(
			array(
				'id'                => 5,
				'name'              => 'Lucía',
				'surnames'          => 'Navarro Gil',
				'invited_by'        => '',
				'incompatibilities' => 'Diego',
				'notes'             => '',
				'physical_letters'  => 2,
				'withdrawn_at'      => null,
			),
			$overrides
		);
	}

	public function test_the_value_is_wrapped_so_it_can_sit_next_to_its_label() {
		$html = $this->cell( 'incompatibilities', $this->attendant() );

		$this->assertMatchesRegularExpression( '#^<td [^>]*data-colname="[^"]+"[^>]*><div class="dfxprl-cell-value">Diego</div></td>$#', $html );
		$this->assertStringNotContainsString( 'dfxprl-empty-cell', $html );
	}

	public function test_empty_values_are_marked_to_leave_them_out_of_the_mobile_cards() {
		foreach ( array( 'invited_by', 'notes' ) as $key ) {
			$html = $this->cell( $key, $this->attendant( array( 'incompatibilities' => '' ) ) );
			$this->assertStringContainsString( 'dfxprl-empty-cell', $html, $key );
			// A quiet dash on screen, the word for screen readers
			$this->assertStringContainsString( '<span class="description" aria-hidden="true">—</span>', $html, $key );
			$this->assertStringContainsString( 'screen-reader-text', $html, $key );
		}
	}

	public function test_the_name_carries_the_surnames_and_the_total_of_letters_for_the_card_title() {
		$html = $this->cell( 'name', $this->attendant(), 5 );

		// 5 messages and 2 physical letters
		$this->assertMatchesRegularExpression( '#<span class="dfxprl-card-total"><strong>7</strong><small>[^<]+</small></span>#', $html );

		$this->assertStringContainsString( 'Lucía <span class="dfxprl-card-surnames">Navarro Gil</span>', $html );
	}
}
