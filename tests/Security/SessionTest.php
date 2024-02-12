<?php

use Fastpress\Security\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    private Session $session;
    private array $testSessionArray;

    protected function setUp(): void
    {
        $this->testSessionArray = [];
        $this->session = new Session($this->testSessionArray);
    }


    /**
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testSessionStart(): void
    {
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    /**
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testSetAndGet(): void
    {
        $this->session->set('key', 'value');
        $this->assertEquals('value', $this->session->get('key'));
    }

    /**
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testSetAndGetFlash(): void
    {
        $this->session->setFlash('flashKey', 'flashValue');
        $this->assertEquals('flashValue', $this->session->getFlash('flashKey'));
        $this->assertNull($this->session->getFlash('flashKey'));
    }

    /**
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testHasFlash(): void
    {
        $this->session->setFlash('flashKey', 'flashValue');
        $this->assertTrue($this->session->hasFlash('flashKey'));
        $this->session->getFlash('flashKey');
        $this->assertFalse($this->session->hasFlash('flashKey'));
    }

    /**
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testDelete(): void
    {
        $this->session->set('key', 'value');
        $this->session->delete('key');
        $this->assertNull($this->session->get('key'));
    }

    /**
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */
    public function testRegenerateId(): void
    {
        $oldSessionId = session_id();
        $this->session->regenerateId();
        $newSessionId = session_id();

        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDestroy(): void
    {
        $this->session->set('key', 'value');
        $this->session->destroy();

        // Start a new session to check if the data was cleared
        session_start();
        $this->assertArrayNotHasKey('key', $_SESSION);
        session_destroy();
    }

    /**
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */    
    public function testArrayAccess(): void
    {
        $this->session['key'] = 'value';
        $this->assertTrue(isset($this->session['key']));
        $this->assertEquals('value', $this->session['key']);
        unset($this->session['key']);
        $this->assertFalse(isset($this->session['key']));
    }

    /**
    * @runInSeparateProcess
    * @preserveGlobalState disabled
    */    
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
