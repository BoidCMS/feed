<?php defined( 'App' ) or die( 'BoidCMS' );
/**
 *
 * RSS Feed
 *
 * @package BoidCMS
 * @subpackage Feed
 * @author Shoaiyb Sysa
 * @version 1.0.0
 */

global $App;
$App->set_action( 'install', 'feed_init' );
$App->set_action( 'uninstall', 'feed_shut' );
$App->set_action( 'slug_taken', 'feed_slug' );
$App->set_action( 'site_head', 'feed_head' );
$App->set_action( 'render', 'feed_render' );
$App->set_action( 'admin', 'feed_admin' );

/**
 * Initiate Feed, first time install
 * @param string $plugin
 * @return void
 */
function feed_init( string $plugin ): void {
  global $App;
  if ( 'feed' === $plugin ) {
    $App->set( 5, 'feed' );
  }
}

/**
 * Free database space, while uninstalled
 * @param string $plugin
 * @return void
 */
function feed_shut( string $plugin ): void {
  global $App;
  if ( 'feed' === $plugin ) {
    $App->unset( 'feed' );
  }
}

/**
 * Escape text
 * @param string $value
 * @return string
 */
function feed_esc( string $value ): string {
  global $App;
  return $App->esc( $value );
}

/**
 * Get database value
 * @param string $index
 * @param bool $esc
 * @return string
 */
function feed_get( string $index, bool $esc = true ): string {
  global $App;
  $value = $App->get( $index );
  return ( $esc ? feed_esc( $value ) : $value );
}

/**
 * Feed taken slug
 * @return string
 */
function feed_slug(): string {
  return ',feed/,';
}

/**
 * HTML head link
 * @return string
 */
function feed_head(): string {
  $html = '<link rel="alternate" type="application/rss+xml" title="%s Feed" href="%sfeed/">';
  return sprintf( $html, feed_get( 'title' ), feed_get( 'url' ) );
}

/**
 * Feed posts
 * @return array
 */
function feed_posts(): array {
  global $App;
  $posts = array();
  $pages = $App->data()[ 'pages' ];
  foreach ( $pages as $slug => $p ) {
    if ( $p[ 'type' ] === 'post' && $p[ 'pub' ] ) {
      $posts[ $slug ] = $p;
    }
  }
  $max = $App->get( 'feed' );
  return array_slice( $posts, 0, $max, true );
}

/**
 * Feed XML
 * @return void
 */
function feed_xml(): void {
  $posts = feed_posts();
  header( 'Content-Type: text/xml' );
  $xml = '<?xml version="1.0" encoding="UTF-8"?>';
  $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
  $xml .= '<channel>';
  $xml .= '<title>' . feed_get( 'title' ) . '</title>';
  $xml .= '<link>' . feed_get( 'url' ) . '</link>';
  $xml .= '<atom:link href="' . feed_get( 'url' ) . 'feed/" rel="self" type="application/rss+xml"/>';
  $xml .= '<description>' . strip_tags( feed_get( 'descr', false ) ) . '</description>';
  $xml .= '<language>' . feed_get( 'lang' ) . '</language>';
  $xml .= '<lastBuildDate>' . date( DATE_RSS ) . '</lastBuildDate>';
  $xml .= '<generator>BoidCMS</generator>';
  foreach ( $posts as $slug => $p ) {
    $xml .= '<item>';
    $xml .= '<title>' . feed_esc( $p[ 'title' ] ) . '</title>';
    $xml .= '<link>' . feed_get( 'url' ) . feed_esc( $slug ) . '</link>';
    $xml .= '<description>' . feed_esc( $p[ 'descr' ] ) . '</description>';
    $xml .= '<pubDate>' . date( DATE_RSS, strtotime( $p[ 'date' ] ) ) . '</pubDate>';
    $xml .= '<guid isPermaLink="false">' . md5( $slug ) . '</guid>';
    $xml .= '</item>';
  }
  $xml .= '</channel>';
  $xml .= '</rss>';
  $dom = new DOMDocument();
  $dom->formatOutput = true;
  $dom->loadXML( $xml );
  $xml = $dom->saveXML();
  $xml = trim( $xml );
  exit( $xml );
}

/**
 * XML Feed page render
 * @return void
 */
function feed_render(): void {
  global $App;
  switch ( $App->page ) {
    case 'feed/':
      feed_xml();
      break;
  }
}

/**
 * Admin settings
 * @return void
 */
function feed_admin(): void {
  global $App, $layout, $page;
  switch ( $page ) {
    case 'feed':
      $layout[ 'title' ] = 'Feed';
      $layout[ 'content' ] = '
      <form action="' . $App->admin_url( '?page=feed', true ) . '" method="post">
        <label for="feed" class="ss-label">Number of items to display <span class="ss-red">*</span></label>
        <input type="number" id="feed" name="feed" min="1" value="' . $App->get( 'feed' ) . '" class="ss-input ss-mobile ss-w-6 ss-mx-auto" required>
        <input type="hidden" name="token" value="' . $App->token() . '">
        <input type="submit" name="save" value="Save" class="ss-btn ss-mobile ss-w-5">
      </form>';
      if ( isset( $_POST[ 'save' ] ) ) {
        $App->auth();
        $feed = ( $_POST[ 'feed' ] ?? 1 );
        if ( $App->set( $feed, 'feed' ) ) {
          $App->alert( 'Settings saved successfully.', 'success' );
          $App->go( $App->admin_url( '?page=feed' ) );
        }
        $App->alert( 'Failed to save settings, please try again.', 'error' );
        $App->go( $App->admin_url( '?page=feed' ) );
      }
      require_once $App->root( 'app/layout.php' );
      break;
  }
}
?>
