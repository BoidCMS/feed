<?php defined( 'App' ) or die( 'BoidCMS' );
/**
 *
 * RSS Feed
 *
 * @package Plugin_Feed
 * @author Shuaib Yusuf Shuaib
 * @version 1.0.0
 */

global $App;
$App->set_action( 'install', 'feed_install' );
$App->set_action( 'uninstall', 'feed_uninstall' );
$App->set_action( array( 'create_success', 'update_success', 'delete_success', 'generate_feed' ), 'generate_feed' );
$App->set_action( 'site_head', 'feed_head' );
$App->set_action( 'admin', 'feed_admin' );

/**
 * Initialize Feed, first time install
 * @param string $plugin
 * @return void
 */
function feed_install( string $plugin ): void {
  global $App;
  if ( 'feed' === $plugin ) {
    $App->set( 5, 'feed' );
    generate_feed();
  }
}

/**
 * Free database space, while uninstalled
 * @param string $plugin
 * @return void
 */
function feed_uninstall( string $plugin ): void {
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
 * HTML head link
 * @return string
 */
function feed_head(): string {
  $html = '<link rel="alternate" type="application/rss+xml" title="%s Feed" href="%sfeed.xml">';
  return sprintf( $html, feed_get( 'title' ), feed_get( 'url' ) );
}

/**
 * Feed posts
 * @param ?int $max
 * @return array
 */
function feed_posts( ?int $max = null ): array {
  global $App;
  $posts = array();
  $pages = $App->data()[ 'pages' ];
  foreach ( $pages as $slug => $p ) {
    if ( $p[ 'type' ] === 'post' && $p[ 'pub' ] ) {
      $posts[ $slug ] = $p;
    }
  }
  $max ??= $App->get( 'feed' );
  return array_slice( $posts, 0, $max, true );
}

/**
 * Feed XML
 * @return DOMDocument
 */
function feed_xml(): DOMDocument {
  $posts = feed_posts();
  $xml = '<?xml version="1.0" encoding="UTF-8"?>';
  $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
  $xml .= '<channel>';
  $xml .= '<title>' . feed_get( 'title' ) . '</title>';
  $xml .= '<link>' . feed_get( 'url' ) . '</link>';
  $xml .= '<atom:link href="' . feed_get( 'url' ) . 'feed.xml" rel="self" type="application/rss+xml"/>';
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
  $doc = new DOMDocument();
  $doc->formatOutput = true;
  $doc->loadXML( $xml );
  return $doc;
}

/**
 * Feed generator
 * @return bool
 */
function generate_feed(): bool {
  global $App;
  $doc = feed_xml();
  $file = $App->root( 'feed.xml' );
  return ( bool ) $doc->save( $file );
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
        <p class="ss-small ss-gray ss-mb-5">This is the maximum number of posts to show in the feed.</p>
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
