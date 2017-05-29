<?php

/**
 * Testing the controller functionality.
 *
 * @category  Testing
 * @package   XH
 * @author    The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @copyright 2014-2017 The CMSimple_XH developers <http://cmsimple-xh.org/?The_Team>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://cmsimple-xh.org/
 */

namespace XH;

use PHPUnit_Extensions_MockFunction;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

/**
 * Testing the handling of page data editor requests.
 *
 * @category Testing
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 * @since    1.6.3
 */
class ControllerPageDataEditorTest extends TestCase
{
    /**
     * The test subject.
     *
     * @var Controller
     */
    protected $subject;

    /**
     * The page data editor mock.
     *
     * @var PageDataEditor
     */
    protected $pageDataEditorMock;

    /**
     * Sets up the test fixture.
     *
     * @return void
     */
    public function setUp()
    {
        $this->subject = $this->getMock('XH\Controller', array('makePageDataEditor'));
        $this->pageDataEditorMock = $this->getMockBuilder('XH\PageDataEditor')
            ->disableOriginalConstructor()->getMock();
        $this->subject->expects($this->any())->method('makePageDataEditor')
            ->will($this->returnValue($this->pageDataEditorMock));
    }

    /**
     * Tests that PageDataEditor::process() is called.
     *
     * @return void
     */
    public function testCallsProcess()
    {
        $this->pageDataEditorMock->expects($this->once())->method('process');
        $this->subject->handlePageDataEditor();
    }
}
