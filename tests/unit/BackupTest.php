<?php

/**
 * Testing the backup functionality.
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

class BackupTest extends TestCase
{
    private $contentFolder;

    private $subject;

    public function setUp()
    {
        global $pth, $cf, $tx;

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('content'));
        $this->contentFolder = vfsStream::url('content/');
        $pth = array(
            'folder' => array(
                'classes' => './cmsimple/classes/'
            ),
            'file' => array('content' => "{$this->contentFolder}content.htm")
        );
        $cf = array(
            'backup' => array('numberoffiles' => '3')
        );
        $tx = array(
            'filetype' => array('backup' => 'backup'),
            'result' => array(
                'created' => 'created',
                'deleted' => 'deleted'
            )
        );
        (new PHPUnit_Extensions_MockFunction('utf8_ucfirst', $this))
            ->expects($this->any())->will($this->returnArgument(0));
        $this->subject = new Backup(array($this->contentFolder));
    }

    /**
     * @dataProvider dataForIsContentBackup
     */
    public function testIsContentBackup($filename, $regularOnly, $expected)
    {
        $this->assertSame($expected, XH_isContentBackup($filename, $regularOnly));
    }

    public function dataForIsContentBackup()
    {
        return array(
            array('20140503_192021_content.htm', true, true),
            array('20140503_1920_content.htm', true, false),
            array('20140503_192021_special.htm', true, false),
            array('20140503_192021_special.html', true, false),
            array('2013-07-11-01-02-03-content.htm', true, false),
            array('20140503_192021_content.htm', false, true),
            array('20140503_1920_content.htm', false, false),
            array('20140503_192021_special.htm', false, true),
            array('20140503_192021_special.html', false, false),
            array('2013-07-11-01-02-03-content.htm', false, false)
        );
    }

    public function testFailedBackupReportsError()
    {
        $eSpy = new PHPUnit_Extensions_MockFunction('e', $this->subject);
        $eSpy->expects($this->once())->with($this->equalTo('cntsave'), $this->equalTo('backup'));
        file_put_contents("{$this->contentFolder}content.htm", '');
        chmod($this->contentFolder, 0444);
        // HACK: we can't use try-catch here, because that would prevent e()
        // from being called, so we temporarily disable the error_reporting
        $errorReporting = error_reporting(0);
        $this->subject->execute();
        error_reporting($errorReporting);
    }

    public function testCreatesBackupWhenNoBackupIsThere()
    {
        touch("{$this->contentFolder}content.htm");
        $this->subject->execute();
        $this->assertCount(4, scandir($this->contentFolder));
    }

    public function testCreatesBackupWhenLatestBackupIsOutdated()
    {
        touch("{$this->contentFolder}19700101_000102_content.htm");
        file_put_contents("{$this->contentFolder}content.htm", 'foo');
        $this->subject->execute();
        $this->assertCount(5, scandir($this->contentFolder));
    }

    public function testDoesntCreateBackupWhenLatestBackupIsUpToDate()
    {
        touch("{$this->contentFolder}19700101_000102_content.htm");
        touch("{$this->contentFolder}content.htm");
        $this->subject->execute();
        $this->assertCount(4, scandir($this->contentFolder));
    }

    public function testReportsCreationOfBackup()
    {
        touch("{$this->contentFolder}content.htm");
        $matcher = array(
            'tag' => 'p',
            'attributes' => array('class' => 'xh_info'),
            'content' => 'created'
        );
        @$this->assertTag($matcher, $this->subject->execute());
    }

    public function testDeletesTooOldBackups()
    {
        file_put_contents("{$this->contentFolder}content.htm", 'foo');
        touch("{$this->contentFolder}19700101_000102_content.htm");
        touch("{$this->contentFolder}19700102_000102_content.htm");
        touch("{$this->contentFolder}19700103_000102_content.htm");
        touch("{$this->contentFolder}19700104_000102_content.htm");
        $this->subject->execute();
        $this->assertFileNotExists(
            "{$this->contentFolder}19700101_000102_content.htm"
        );
        $this->assertFileNotExists(
            "{$this->contentFolder}19700102_000102_content.htm"
        );
    }

    public function testKeepsYoungEnoughBackups()
    {
        file_put_contents("{$this->contentFolder}content.htm", 'foo');
        touch("{$this->contentFolder}19700101_000102_content.htm");
        touch("{$this->contentFolder}19700102_000102_content.htm");
        $this->subject->execute();
        $this->assertFileExists(
            "{$this->contentFolder}19700101_000102_content.htm"
        );
        $this->assertFileExists(
            "{$this->contentFolder}19700102_000102_content.htm"
        );
    }

    public function testReportsDeletionOfBackup()
    {
        file_put_contents("{$this->contentFolder}content.htm", 'foo');
        touch("{$this->contentFolder}19700101_000102_content.htm");
        touch("{$this->contentFolder}19700102_000102_content.htm");
        touch("{$this->contentFolder}19700103_000102_content.htm");
        $matcher = array(
            'tag' => 'p',
            'attributes' => array('class' => 'xh_info'),
            'content' => 'deleted'
        );
        @$this->assertTag($matcher, $this->subject->execute());
    }

    public function testReportsDeletionFailure()
    {
        $eSpy = new PHPUnit_Extensions_MockFunction('e', $this->subject);
        $eSpy->expects($this->once())->with($this->equalTo('cntdelete'), $this->equalTo('backup'));
        file_put_contents("{$this->contentFolder}content.htm", 'foo');
        touch("{$this->contentFolder}19700101_000102_content.htm");
        touch("{$this->contentFolder}19700102_000102_content.htm");
        touch("{$this->contentFolder}19700103_000102_content.htm");
        $unlinkStub = new PHPUnit_Extensions_MockFunction('unlink', $this->subject);
        $unlinkStub->expects($this->any())->will($this->returnValue(false));
        $this->subject->execute();
    }

    public function testDoesNothingWhenNumberoffilesIsEmpty()
    {
        global $cf;

        $cf['backup']['numberoffiles'] = '0';
        $this->subject = new Backup(array($this->contentFolder));
        touch("{$this->contentFolder}content.htm");
        $eSpy = new PHPUnit_Extensions_MockFunction('e', $this->subject);
        $eSpy->expects($this->never());
        $this->assertEquals('', $this->subject->execute());
    }

    public function testMultipleBackupFolders()
    {
        $subject = $this->getMock('XH\Backup', array('backupSingleFolder'), array(array('foo', 'bar')));
        $subject->expects($this->exactly(2))->method('backupSingleFolder');
        $subject->execute($subject);
    }
}
