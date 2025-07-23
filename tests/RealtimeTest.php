<?php
require_once __DIR__.'/../includes/class-school-sports-api-realtime.php';
require_once __DIR__.'/../includes/class-school-sports-api-api.php';

use PHPUnit\Framework\TestCase;

class RealtimeTest extends TestCase {
    public function setUp(): void {
        $this->realtime = new School_Sports_API_Realtime('school-sports-api', '1.0.0');
    }

    public function testDetectChangesWhenResultChanges() {
        $old = [[
            'naziv' => 'Sport',
            'natjecanja' => [[
                'faza' => [[
                    'grupa' => [[
                        'utakmice' => [[
                            'brojUtakmice' => 1,
                            'rezultatPrikaz' => '1:0'
                        ]]
                    ]]
                ]]
            ]]
        ]];
        $new = [[
            'naziv' => 'Sport',
            'natjecanja' => [[
                'faza' => [[
                    'grupa' => [[
                        'utakmice' => [[
                            'brojUtakmice' => 1,
                            'rezultatPrikaz' => '2:0'
                        ]]
                    ]]
                ]]
            ]]
        ]];
        $ref = new ReflectionClass($this->realtime);
        $method = $ref->getMethod('detect_changes');
        $method->setAccessible(true);
        $changes = $method->invoke($this->realtime, $old, $new);
        $this->assertNotEmpty($changes);
        $this->assertEquals('result_changed', $changes[0]['type']);
    }
}
