<?php

namespace Tests\Unit;

use App\Models\Schedule;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class EventsTest extends TestCase
{
    private Carbon $referenceDate;

    private Carbon $referenceDate15;

    private const string TODAY = '2022-03-23'; // dow: 3

    private const string TOMORROW = '2022-03-24'; // dow: 4

    private const string YESTERDAY = '2022-03-22'; // dow: 2

    protected function setUp(): void
    {
        parent::setUp();

        $this->referenceDate = new Carbon(EventsTest::TODAY.' 12:00:00');

        $this->referenceDate15 = new Carbon(EventsTest::TODAY.' 15:00:00');
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_today_is_today()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            EventsTest::TODAY,
            EventsTest::TODAY,
            '14:00:00',
            '17:00:00',
            '1234567',
            $this->referenceDate
        );
        $this->assertEquals(EventsTest::TODAY.' 14:00:00', $real_start_date);
    }

    public function test_does_not_repeat_start()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            EventsTest::TODAY,
            EventsTest::TODAY,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate
        );
        $this->assertEquals(EventsTest::TODAY.' 14:00:00', $real_start_date);
    }

    public function test_does_not_repeat_end()
    {
        $real_end_date = Schedule::calculateEndDateTime(
            EventsTest::TODAY,
            EventsTest::TODAY,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate
        );
        $this->assertEquals(EventsTest::TODAY.' 17:00:59', $real_end_date);
    }

    public function test_does_not_repeat_start_tomorrow()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            EventsTest::TOMORROW,
            EventsTest::TOMORROW,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate
        );
        $this->assertEquals(EventsTest::TOMORROW.' 14:00:00', $real_start_date);
    }

    public function test_does_not_repeat_end_tomorrow()
    {
        $real_end_date = Schedule::calculateEndDateTime(
            EventsTest::TOMORROW,
            EventsTest::TOMORROW,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate
        );
        $this->assertEquals(EventsTest::TOMORROW.' 17:00:59', $real_end_date);
    }

    public function test_does_not_repeat_start_yesterday()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            EventsTest::YESTERDAY,
            EventsTest::YESTERDAY,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate
        );
        $this->assertEquals(EventsTest::YESTERDAY.' 14:00:00', $real_start_date);
    }

    public function test_does_not_repeat_end_yesterday()
    {
        $real_end_date = Schedule::calculateEndDateTime(
            EventsTest::YESTERDAY,
            EventsTest::YESTERDAY,
            '14:00:00',
            '17:00:00',
            null,
            $this->referenceDate
        );
        $this->assertEquals(EventsTest::YESTERDAY.' 17:00:59', $real_end_date);
    }

    public function test_overnight_event()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            EventsTest::TODAY, // 3
            EventsTest::TOMORROW, // 4
            '20:00:00',
            '13:00:00',
            '3',
            $this->referenceDate // 3, 12:00:00
        );
        $this->assertEquals(EventsTest::TODAY.' 20:00:00', $real_start_date);
    }

    public function test_overnight_event2()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            EventsTest::TODAY, // 3
            EventsTest::TOMORROW, // 4
            '20:00:00',
            '13:00:00',
            '4',
            $this->referenceDate // 3, 12:00:00
        );
        $this->assertEquals(EventsTest::TOMORROW.' 20:00:00', $real_start_date);
    }

    public function test_overnight_event3()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            EventsTest::TODAY, // 3
            EventsTest::TOMORROW, // 4
            '20:00:00',
            '13:00:00',
            '34',
            $this->referenceDate // 3, 12:00:00
        );
        $this->assertEquals(EventsTest::TODAY.' 20:00:00', $real_start_date);
    }

    public function test_overnight_event_starting_tomorrow_without_boundaries()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            null,
            null,
            '20:00:00',
            '13:00:00',
            '4',
            $this->referenceDate // 3, 12:00:00
        );
        $this->assertEquals(EventsTest::TOMORROW.' 20:00:00', $real_start_date);
    }

    public function test_overnight_event_without_boundaries_still_running()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            null,
            null,
            '20:00:00',
            '13:00:00',
            '2',
            $this->referenceDate // 3, 12:00:00
        );
        $this->assertEquals((new Carbon(EventsTest::YESTERDAY.' 20:00:00'))->format('Y-m-d H:i:s'), $real_start_date);
    }

    public function test_overnight_event_without_boundaries_already_finished_today()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            null,
            null,
            '20:00:00',
            '13:00:00',
            '2',
            $this->referenceDate15 // 3, 12:00:00
        );
        $this->assertEquals((new Carbon(EventsTest::YESTERDAY.' 20:00:00'))->addWeek(1)->format('Y-m-d H:i:s'), $real_start_date);
    }

    public function test_overnight_event_the_day_after_before_it_finishes()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            EventsTest::YESTERDAY,
            EventsTest::TODAY,
            '20:00:00',
            '13:00:00',
            '2',
            $this->referenceDate // 3, 12:00:00
        );
        $this->assertEquals(EventsTest::YESTERDAY.' 20:00:00', $real_start_date);
    }

    public function test_overnight_event_the_day_after_it_finishes()
    {
        $real_start_date = Schedule::calculateStartDateTime(
            EventsTest::YESTERDAY,
            EventsTest::TODAY,
            '20:00:00',
            '13:00:00',
            '2',
            $this->referenceDate15 // 3, 15:00:00
        );
        $this->assertEquals((new Carbon(EventsTest::YESTERDAY.' 20:00:00'))->addDays(7)->format('Y-m-d H:i:s'), $real_start_date);
    }
}
