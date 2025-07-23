<?php
require_once __DIR__.'/../includes/class-school-sports-api-shortcodes.php';

use PHPUnit\Framework\TestCase;

class ShortcodesTest extends TestCase {
    public function setUp(): void {
        $this->shortcodes = new School_Sports_API_Shortcodes('school-sports-api', '1.0.0');
    }

    public function testFilterBySchoolTypeHighSchool() {
        $data = [
            ['natjecanje' => ['spol' => 'Mladići']],
            ['natjecanje' => ['spol' => 'Djevojke']],
            ['natjecanje' => ['spol' => 'Dječaci']],
        ];
        $filtered = $this->invokeFilter($data, 'ss');
        $this->assertCount(2, $filtered);
        $this->assertEquals('Mladići', $filtered[0]['natjecanje']['spol']);
        $this->assertEquals('Djevojke', $filtered[1]['natjecanje']['spol']);
    }

    public function testFilterBySchoolTypeElementary() {
        $data = [
            ['natjecanje' => ['spol' => 'Dječaci']],
            ['natjecanje' => ['spol' => 'Djevojčice']],
            ['natjecanje' => ['spol' => 'Mladići']],
        ];
        $filtered = $this->invokeFilter($data, 'os');
        $this->assertCount(2, $filtered);
        $this->assertEquals('Dječaci', $filtered[0]['natjecanje']['spol']);
        $this->assertEquals('Djevojčice', $filtered[1]['natjecanje']['spol']);
    }

    private function invokeFilter($data, $type) {
        $ref = new ReflectionClass($this->shortcodes);
        $method = $ref->getMethod('filter_by_school_type');
        $method->setAccessible(true);
        return $method->invoke($this->shortcodes, $data, $type);
    }
}
