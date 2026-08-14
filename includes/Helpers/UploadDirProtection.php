<?php
namespace AllI1D\C411\Helpers;

class UploadDirProtection {

    /**
     * Ensure the given directory has an .htaccess denying direct web access
     * to the files it contains (Apache 2.2 and 2.4 syntax), since it lives
     * under the publicly served wp-content/uploads/ tree.
     * @param string $dir
     */
    public static function protect($dir) {
        $htaccess_path = $dir . '/.htaccess';
        if (file_exists($htaccess_path)) {
            return;
        }
        $contents = "# Deny direct access to files in this directory (Apache 2.2)\n"
            . "Order deny,allow\n"
            . "Deny from all\n\n"
            . "# Deny direct access to files in this directory (Apache 2.4+)\n"
            . "<IfModule mod_authz_core.c>\n"
            . "    Require all denied\n"
            . "</IfModule>\n";
        file_put_contents($htaccess_path, $contents);
    }
}
