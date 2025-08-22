<?php
require_once __DIR__ . '/bootstrap_pdo.php';
 declare(strict_types=1);

/** Dump to ZIP format
 *
 * @see     https://www.adminer.org/plugins/#use
 *
 * @uses    ZipArchive, tempnam("")
 *
 * @author  Jakub Vrana, https://www.vrana.cz/
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
 */
class AdminerDumpZip
{
    public $filename;

    public $data;

    /**
     * @return array
     */
    public function dumpOutput()
    {
        if (!class_exists('ZipArchive')) {
            return [];
        }

        return ['zip' => 'ZIP'];
    }

    /**
     * @param $string
     * @param $state
     *
     * @return false|string
     */
    public function _zip($string, $state)
    {
        // ZIP can be created without temporary file by gzcompress - see PEAR File_Archive
        $this->data .= $string;
        if ($state & PHP_OUTPUT_HANDLER_END) {
            $zip = new ZipArchive();
            $zipFile = tempnam('', 'zip');
            $zip->open($zipFile, ZipArchive::OVERWRITE); // php://output is not supported
            $zip->addFromString($this->filename, $this->data);
            $zip->close();
            $return = file_get_contents($zipFile);
            unlink($zipFile);

            return $return;
        }

        return '';
    }

    /**
     * @param      $identifier
     * @param bool $multi_table
     */
    public function dumpHeaders($identifier, $multi_table = false)
    {
        if ($_POST['output'] == 'zip') {
            $this->filename = "$identifier." . ($multi_table && preg_match('~[ct]sv~', $_POST['format']) ? 'tar' : $_POST['format']);
            header('Content-Type: application/zip');
            ob_start([
                $this,
                '_zip',
            ]);
        }
    }
}
