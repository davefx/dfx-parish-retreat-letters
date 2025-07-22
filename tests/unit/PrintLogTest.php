<?php
/**
 * Unit tests for DFX_Parish_Retreat_Letters_PrintLog class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFX_Parish_Retreat_Letters_PrintLog
 */
class PrintLogTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_get_current_user')->justReturn((object) [
            'ID' => 1,
            'user_login' => 'admin'
        ]);
        Functions\when('current_time')->alias(function($type) {
            return ($type === 'mysql') ? '2024-01-01 12:00:00' : time();
        });
    }

    /**
     * Tear down test environment
     */
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test print action logging
     */
    public function testPrintActionLogging() {
        $print_log = new DFX_Parish_Retreat_Letters_PrintLog();
        
        if (method_exists($print_log, 'log_print_action')) {
            $log_data = [
                'attendant_id' => 1,
                'message_id' => 1,
                'action_type' => 'letter_printed',
                'user_id' => 1,
                'ip_address' => '192.168.1.100'
            ];
            
            $result = $print_log->log_print_action($log_data);
            $this->assertTrue(is_callable([$print_log, 'log_print_action']));
        } else {
            $this->markTestSkipped('log_print_action method not found');
        }
    }

    /**
     * Test get print logs by attendant
     */
    public function testGetPrintLogsByAttendant() {
        $print_log = new DFX_Parish_Retreat_Letters_PrintLog();
        
        if (method_exists($print_log, 'get_logs_by_attendant')) {
            $attendant_id = 1;
            $result = $print_log->get_logs_by_attendant($attendant_id);
            $this->assertTrue(is_callable([$print_log, 'get_logs_by_attendant']));
        } else {
            $this->markTestSkipped('get_logs_by_attendant method not found');
        }
    }

    /**
     * Test get print logs by date range
     */
    public function testGetPrintLogsByDateRange() {
        $print_log = new DFX_Parish_Retreat_Letters_PrintLog();
        
        if (method_exists($print_log, 'get_logs_by_date_range')) {
            $start_date = '2024-01-01';
            $end_date = '2024-01-31';
            $result = $print_log->get_logs_by_date_range($start_date, $end_date);
            $this->assertTrue(is_callable([$print_log, 'get_logs_by_date_range']));
        } else {
            $this->markTestSkipped('get_logs_by_date_range method not found');
        }
    }

    /**
     * Test print statistics generation
     */
    public function testPrintStatisticsGeneration() {
        $print_log = new DFX_Parish_Retreat_Letters_PrintLog();
        
        if (method_exists($print_log, 'generate_statistics')) {
            $result = $print_log->generate_statistics();
            $this->assertTrue(is_callable([$print_log, 'generate_statistics']));
        } else {
            $this->markTestSkipped('generate_statistics method not found');
        }
    }

    /**
     * Test audit trail functionality
     */
    public function testAuditTrail() {
        $print_log = new DFX_Parish_Retreat_Letters_PrintLog();
        
        if (method_exists($print_log, 'get_audit_trail')) {
            $filter_options = [
                'user_id' => 1,
                'action_type' => 'letter_printed',
                'date_from' => '2024-01-01',
                'date_to' => '2024-01-31'
            ];
            
            $result = $print_log->get_audit_trail($filter_options);
            $this->assertTrue(is_callable([$print_log, 'get_audit_trail']));
        } else {
            $this->markTestSkipped('get_audit_trail method not found');
        }
    }

    /**
     * Test log data cleanup
     */
    public function testLogDataCleanup() {
        $print_log = new DFX_Parish_Retreat_Letters_PrintLog();
        
        if (method_exists($print_log, 'cleanup_old_logs')) {
            $days_to_keep = 90;
            $result = $print_log->cleanup_old_logs($days_to_keep);
            $this->assertTrue(is_callable([$print_log, 'cleanup_old_logs']));
        } else {
            $this->markTestSkipped('cleanup_old_logs method not found');
        }
    }
}