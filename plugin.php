<?php defined( 'App' ) or die( 'BoidCMS' );
/**
 *
 * Menu – A customizable menu builder
 *
 * @package Plugin_Menu
 * @author Shuaib Yusuf Shuaib
 * @version 1.0.0
 */

if ( 'menu' !== basename( __DIR__ ) ) return;

global $App;
$App->set_action( 'install', 'menu_install' );
$App->set_action( 'uninstall', 'menu_uninstall' );
$App->set_action( 'site_nav', 'menu_site_nav' );
$App->set_action( 'menu_end', 'menu_script' );
$App->set_action( 'admin', 'menu_admin' );

/**
 * Initialize Menu, first time install
 * @param string $plugin
 * @return void
 */
function menu_install( string $plugin ): void {
  global $App;
  if ( 'menu' === $plugin ) {
    $config = array();
    
    $config[0] = array();
    $config[0][ 'text' ] = 'Home';
    $config[0][ 'link' ] = $App->url();
    
    $config[1] = array();
    $config[1][ 'text' ] = 'About';
    $config[1][ 'link' ] = $App->url( 'about' );
    
    $config[2] = array();
    $config[2][ 'text' ] = 'Contact';
    $config[2][ 'link' ] = $App->url( 'contact' );
    
    $App->set( $config, 'menu' );
  }
}

/**
 * Free database space, while uninstalled
 * @param string $plugin
 * @return void
 */
function menu_uninstall( string $plugin ): void {
  global $App;
  if ( 'menu' === $plugin ) {
    $App->unset( 'menu' );
  }
}

/**
 * Build menu
 * @param string $format
 * @return string
 */
function menu_site_nav( string $format ): string {
  global $App;
  $site_nav = '';
  foreach ( $App->get( 'menu' ) as $menu ) {
    $text = $menu[ 'text' ];
    $link = $App->esc( $menu[ 'link' ] );
    $site_nav .= sprintf( $format, $link, $text );
  }
  return $site_nav;
}

/**
 * Admin settings
 * @return void
 */
function menu_admin(): void {
  global $App, $layout, $page;
  switch ( $page ) {
    case 'menu':
    $config = $App->get( 'menu' );
    $layout[ 'title' ] = 'Menu';
    $layout[ 'content' ] = '
    <form action="' . $App->admin_url( '?page=menu', true ) . '" method="post">
      <fieldset id="menu" class="ss-fieldset ss-list ss-mobile ss-w-7 ss-mx-auto">
        <button type="button" onclick="document.querySelector(`#menu`).innerHTML+=createMenu();removeEmptyNotice();addEmptyNotice()" class="ss-button ss-card">＋ New Item</button>
        <hr class="ss-hr">';
    foreach ( $config as $index => $menu ) {
      $layout[ 'content' ] .= '
        <li id="item_' . $index . '">
          <label class="ss-label">Priority: ' . $index . '</label>
          <input type="text" name="menu[' . $index . '][text]" value="' . $App->esc( $menu[ 'text' ] ) . '" class="ss-input">
          <p class="ss-small ss-mb-5">The text that will be displayed on the menu item in the navigation menu.</p>
          <input type="text" name="menu[' . $index . '][link]" value="' . $App->esc( $menu[ 'link' ] ) . '" class="ss-input">
          <p class="ss-small ss-mb-5">The URL that the menu item will link to when clicked.</p>
          <div class="ss-btn-group">
            <button type="button" onclick="moveMenuUp(this.parentNode)" class="ss-btn ss-info">&uarr;</button>
            <button type="button" onclick="deleteMenu(this.parentNode)" class="ss-btn ss-error">&times;</button>
            <button type="button" onclick="moveMenuDown(this.parentNode)" class="ss-btn ss-info">&darr;</button>
          </div>
        </li>';
    }
    $layout[ 'content' ] .= '
      </fieldset>
      <input type="hidden" name="token" value="' . $App->token() . '">
      <input type="submit" name="save" value="Save" class="ss-btn ss-mobile ss-w-5">
    </form>';
    if ( isset( $_POST[ 'save' ] ) ) {
      $App->auth();
      $config = array_values( $_POST[ 'menu' ] ?? [] );
      if ( $App->set( $config, 'menu' ) ) {
        $App->alert( 'Settings saved successfully.', 'success' );
        $App->go( $App->admin_url( '?page=menu' ) );
      }
      $App->alert( 'Failed to save settings, please try again.', 'error' );
      $App->go( $App->admin_url( '?page=menu' ) );
    }
    require_once $App->root( 'app/layout.php' );
    break;
  }
}

/**
 * Helper for compatibility
 * @return string
 */
function menu(): string {
  global $App;
  $format = '<li><a href="%s">%s</a></li>';
  $action = $App->get_action( 'site_nav', $format );
  $format = '<ul data-plugin="menu" style="list-style-type:none;margin-left:0">%s</ul>';
  return sprintf( $format, $action );
}

/**
 * JavaScript helpers
 * @return string
 */
function menu_script(): string {
  return <<<EOL
  <script>
  function createMenu() {
    var menu = document.querySelector("#menu");
    var text = escapePrompt(prompt("Enter the menu text"));
    var link = escapePrompt(prompt("Enter the menu link"));
    var index = menu.children.length - 2;
    if (text && link) {
      return `
      <li id="item_\${index}">
        <label class="ss-label">Priority: \${index}</label>
        <input type="text" name="menu[\${index}][text]" value="\${text}" class="ss-input">
        <p class="ss-small ss-mb-5">The text that will be displayed on the menu item in the navigation menu.</p>
        <input type="text" name="menu[\${index}][link]" value="\${link}" class="ss-input">
        <p class="ss-small ss-mb-5">The URL that the menu item will link to when clicked.</p>
        <div class="ss-btn-group">
          <button type="button" onclick="moveMenuUp(this.parentNode)" class="ss-btn ss-info">&uarr;</button>
          <button type="button" onclick="deleteMenu(this.parentNode)" class="ss-btn ss-error">&times;</button>
          <button type="button" onclick="moveMenuDown(this.parentNode)" class="ss-btn ss-info">&darr;</button>
        </div>
      </li>`;
    }
    return "";
  }

  function deleteMenu(element) {
    if (confirm("Are you sure you want to delete this item?")) {
      element.parentNode.remove();
      addEmptyNotice();
    }
  }

  function moveMenuUp(element) {
    var present = element.parentNode;
    var present_copy = present.innerHTML;
    var present_label = present.querySelector("label").innerText;
    var prev = present.previousElementSibling;

    if (prev && prev.nodeName === "LI") {
      var prev_copy = prev.innerHTML;
      var prev_label = prev.querySelector("label").innerText;

      prev.innerHTML = present_copy;
      present.innerHTML = prev_copy;

      present.querySelector("label").innerText = present_label;
      prev.querySelector("label").innerText = prev_label;
    }
  }

  function moveMenuDown(element) {
    var present = element.parentNode;
    var present_copy = present.innerHTML;
    var present_label = present.querySelector("label").innerText;
    var next = present.nextElementSibling;

    if (next && next.nodeName === "LI") {
      var next_copy = next.innerHTML;
      var next_label = next.querySelector("label").innerText;

      next.innerHTML = present_copy;
      present.innerHTML = next_copy;

      present.querySelector("label").innerText = present_label;
      next.querySelector("label").innerText = next_label;
    }
  }

  function escapePrompt(text) {
    return text.replace(/\\\/g, "")
               .replace(/&/g, "&amp;")
               .replace(/</g, "&lt;")
               .replace(/>/g, "&gt;")
               .replace(/"/g, "&quot;")
               .replace(/'/g, "&#039;")
               .trim();
  }

  function addEmptyNotice() {
    var menu = document.querySelector("#menu");
    var notice = menu.querySelector("span#empty");
    if (menu.children.length <= 2 && !notice) {
      menu.innerHTML += '<span id="empty" class="ss-tiny">NO ITEM</span>';
    }
  }

  function removeEmptyNotice() {
    var menu = document.querySelector("#menu");
    var notice = menu.querySelector("span#empty");
    if (menu.children.length > 2 && notice) {
      notice.remove();
    }
  }

  document.addEventListener("DOMContentLoaded", addEmptyNotice);
  </script>
  EOL;
}
?>
