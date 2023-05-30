<?php namespace LaravelDuo;


class LaravelDuo extends Duo {

    //Test Local Account Cridentials
    // private $_AKEY = 'DISYRIADY4EWCZS6HZ6VL51dadnnADBSAFMKONAOIHKONSIONOKMSOIJHIFSNKVNNIONIONIOSFNION';
    // private $_IKEY = 'DIJOVX3D24QCTDUTQGX9';
    // private $_SKEY = 'c15wgsgQtmHtCGopm1JD3BC4tQeF3CEjQSWJJKOu';
    // private $_HOST = 'api-c211bf8a.duosecurity.com';

    //Client DUO Account Cridentials
    private $_AKEY = 'DISYRIADY4EWCZS6HZ6VL51dadnnADBSAFMKONAOIHKONSIONOKMSOIJHIFSNKVNNIONIONIOSFNION';
    private $_IKEY = 'DIFSW3KZTD7C4TPC30KC';
    private $_SKEY = 'C1qnzr2eS1RGZpXEvY9YyG9XEpvjvj1LzGOAYkyy';
    private $_HOST = 'api-e6b31c76.duosecurity.com';

    public function get_akey()
    {
        return $this->_AKEY;
    }

    public function get_ikey()
    {
        return $this->_IKEY;
    }

    public function get_skey()
    {
        return $this->_SKEY;
    }

    public function get_host()
    {
        return $this->_HOST;
    }

} 