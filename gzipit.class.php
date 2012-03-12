<?php
/*
 * kinqpinz.info - now in plain text mode!
 * gzipit.class.php - gzip a page pending browser support
 *
 * header: top:    ob_start(); ob_implicit_flush( 0 );
 * footer: bottom: require_once '/path/to/gzipit.class.php';
 *
 * inspired by http://www.mnot.net/cgi_buffer/
 */

class gzipIt
{
  /*
   * uncompressed body
   */
  var $body;

  /*
   * compressed body
   */
  var $gzipBody;

  /*
   * MD5( uncompressed body )
   */
  var $etag;

  /*
   * gzip, x-gzip
   */
  var $gzipTypes;
  var $gzipType;

  /*
   * send gzipped body where supported
   */
  var $hasGzip;
  var $sendBody;

  function gzipIt()
  {
    if( !extension_loaded( 'zlib' ) )
      return FALSE;

    $this->sendBody =TRUE;
    $this->gzipTypes=array( 'gzip','x-gzip' );
    $this->body     =ob_get_contents();
    $this->etag     ='"'.md5( $this->body ).'"';
    ob_end_clean();

    if( $this->gzipIsOn() && $this->hasGzip )
      $this->sendHeaders();
  }

  function gzipIsOn()
  {
    $httpAcceptEncoding=explode( ',',getenv( 'HTTP_ACCEPT_ENCODING' ) );

    foreach( $this->gzipTypes as $gzipType ) {
      if( in_array( $gzipType,$httpAcceptEncoding ) ) {
        $this->gzipType=$gzipType;
        $this->hasGzip =TRUE;
        $this->gzipBody=gzencode( $this->body );
        return TRUE;
      }
    }

    return FALSE;
  }

  function sendHeaders()
  {
    header( 'Content-Encoding: '.$this->gzipType );
    header( 'Vary: Accept-Encoding' );
    header( 'ETag: '.$this->etag );

    $httpIfNoneMatch=explode( ',',getenv( 'HTTP_IF_NONE_MATCH' ) );
    foreach( $httpIfNoneMatch as $_httpIfNoneMatch ) {
      if( trim( $_httpIfNoneMatch ) == $this->etag ) {
        header( 'HTTP/1.0 304 Not Modified' );
        $this->sendBody=FALSE;
        break;
      }
    }

    if( $this->sendBody && $this->hasGzip && $this->gzipType != NULL && $this->gzipBody != NULL ) {
      header( 'Content-Length: '.strlen( $this->gzipBody ) );
      echo $this->gzipBody;
    }
  }
}

new gzipIt;
?>