<?php

class TicketTest extends \PHPUnit\Framework\TestCase
{
    function testTicket() {
        $uid = 40000000;
        $taskid = 1;

        $ticket = \Pepper\Lib\Ticket::getTaskTicket($uid, $taskid);

        $this->assertTrue(\Pepper\Lib\Ticket::checkToken($ticket));
    }
}