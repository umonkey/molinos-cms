<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

interface iBebopCacheEngine
{
    static public function getInstance();
    static public function isAvailable();
    public function getPrefix();
    public function flush($now = false);
}
