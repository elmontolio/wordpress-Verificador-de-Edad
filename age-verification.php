<?php
/*
Plugin Name: Verificador de Edad
Description: Verifica si un usuario tiene la edad suficiente para visitar tu sitio web.
Version: 0.4
Author: Mark Jaquith
Author URI: http://coveredwebservices.com/
Traducción al español: David Montolio

*/

/*
    Copyright 2008 Mark Jaquith (email: mark.gpl@txfx.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( !defined( 'ABSPATH' ) ) { // we're being called directly, for age verification

	if ( file_exists('../../wp-config.php') )
		include('../../wp-config.php');
	elseif ( file_exists('../../../wp-config.php') )
		include('../../../wp-config.php');
	else
		die('Could not find wp-config.php');

	if ( $_POST ) {
		foreach ( array( 'year', 'month', 'day' ) as $unit )
			$_POST['age_' . $unit] = absint( ltrim( $_POST['age_' . $unit], '0' ) );
		if (
			$_POST['age_year'] < 1900 ||
			$_POST['age_month'] < 1 ||
			$_POST['age_month'] > 12 ||
			$_POST['age_day'] < 1 ||
			$_POST['age_day'] > 31
		) {
			wp_redirect( cws_age_verification::plugin_url() . '?wrongformat=1&redirect_to=' . urlencode( stripslashes( $_POST['redirect_to'] ) ) );
			die();
		}
		$dob = $_POST['age_year'] . '-' . zeroise( $_POST['age_month'], 2 ) . '-' . zeroise( $_POST['age_day'], 2 );
		cws_age_verification::set_dob($dob);
		wp_redirect( $_POST['redirect_to'] );
		die();
	} else { ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
	<div align="center"><table height="350" width="896"
 background="" >
<tr><td><table style="float:center">
	<head>
		<title>     Age Verification Required</title>
	</head>
	<body><div style="margin: 70px 0px 0px 15px;">
		<h1>Age Verification Required</h1>
		<?php if ( !$_COOKIE['cws_age_verification_cookie_check'] ) : ?>
			<p>Si bien este sitio web necesita que ingreses tu fecha de nacimiento, tu navegador no est&aacute; aceptando cookies. Por favor activa las cookies e intenta visitanos de nuevo.</p>

			<?php cws_age_verification::footer(); ?>
		<?php endif; ?>
		<?php if ( $_GET['notoldenough'] ) : ?>
			<p><strong>No tienes edad suficiente para ingresar a nuestro sitio web.</strong></p>

		<?php elseif ( $_GET['wrongformat'] ) : ?>
			<p><strong>Tu fecha de nacimiento debe estar en este formato: <code>DD MM AAAA</code>, y debe ser una fecha v&aacute;lida.</strong></p>

		<?php endif; ?>
		<p>    Tienes que tener por lo menos <?php echo cws_age_verification::age_required(); ?> a&ntilde;os para entrar a nuestro sitio web. Por favor ingresa tu fecha de nacimiento:</p>

		<form action="" method="post">
			<input type="hidden" name="redirect_to" value="<?php echo clean_url( stripslashes( $_REQUEST['redirect_to'] ) ); ?>" />
			<?php if ( get_option( 'cws_age_verification_use_dropdowns ' ) ) : ?>
				<select name="age_month">
					<?php
					for ( $i=1; $i<13; $i++ )
						echo '<option value="' . $i . '">' . gmdate( 'F', gmmktime( 0, 0, 0, $i, 1, 0 ) ) . '</option>';
					?>
				</select>
				<select name="age_day">
					<?php
					for ( $i=1; $i<32; $i++ )
						echo '<option value="' . zeroise( $i, 2 ) . '">' . zeroise( $i, 2 ) . '</option>';
					?>
				</select>
				<select name="age_year">
					<?php
					for ( $i = date('Y'); $i > date('Y') - 110; $i-- )
						echo '<option value="' . $i . '">' . $i . '</option>';
					?>
				</select>
			<?php else : // plain text inputs ?>
				<input name="age_day"   type="text" maxlength="2" value="DD"   onfocus="this.value='';" style="width: 2em;" />

				<input name="age_month" type="text" maxlength="2" value="MM"   onfocus="this.value='';" style="width: 2em;" />
				<input name="age_year"  type="text" maxlength="4" value="AAAA" onfocus="this.value='';" style="width: 4em;" />
			<?php endif; ?>
			<input type="submit" value="Enviar &raquo;" />
		</form>
</td></tr>
</table></div>
<?php
		cws_age_verification::footer();
	}
}

class cws_age_verification {

	function footer() {
		echo "</body></div></html>";
		die();
	}

	function check() {
		if ( current_user_can( 'read' ) && get_option( 'cws_age_verification_skip_registered' ) ) {
			// nothing -- let them pass
		} elseif ( !$_COOKIE['cws_age_verification_dob'] ) {
			cws_age_verification::set_test();
			wp_redirect( cws_age_verification::plugin_url() . '?redirect_to=http://' . $_SERVER['HTTP_HOST'] . urlencode($_SERVER['REQUEST_URI'] ) );
			die();
		} elseif ( cws_age_verification::age_required() > cws_age_verification::dob_to_age( $_COOKIE['cws_age_verification_dob'] ) ) {
			cws_age_verification::set_test();
			wp_redirect( cws_age_verification::plugin_url() . '?notoldenough=1&redirect_to=http://' . $_SERVER['HTTP_HOST'] . urlencode($_SERVER['REQUEST_URI'] ) );
			die();
		} else {
			cws_age_verification::set_dob( $_COOKIE['cws_age_verification_dob'] ); // keep-alive
		}
	}

	function plugin_url() {
		return get_option( 'siteurl' ) . '/' . PLUGINDIR . '/' . plugin_basename( __FILE__ );
	}

	function age_required() {
		return absint( get_option( 'cws_age_verification_age' ) );
	}

	function timeout_minutes() {
		return absint( get_option( 'cws_age_verification_timeout' ) );

	}

	function timeout_seconds() {
		return 60 * cws_age_verification::timeout_minutes();
	}

	function set_test() {
		setcookie( 'cws_age_verification_cookie_check', '1', time() + 3600,     COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'cws_age_verification_cookie_check', '1', time() + 3600, SITECOOKIEPATH, COOKIE_DOMAIN );
	}

	function set_dob( $dob ) {
		setcookie( 'cws_age_verification_dob', $dob, time() + cws_age_verification::timeout_seconds(),     COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'cws_age_verification_dob', $dob, time() + cws_age_verification::timeout_seconds(), SITECOOKIEPATH, COOKIE_DOMAIN );
	}

	function dob_to_age( $birthdate ) {
		// birthdate should be in yyyy-mm-dd form
		if ( $birthdate ) {
			$birth = date( 'Ymd', strtotime( $birthdate ) );
			$age = date( 'Y' ) - substr( $birth, 0, 4 );
			if ( date( 'md' ) < substr( $birth, 4, 4 ) )
				--$age;
			return $age;
		}
	}

	function admin() {
		if ( !empty( $_POST ) ) {
			if ( function_exists( 'current_user_can' ) && !current_user_can( 'manage_options' ) )
				die( __( 'Cheatin&#8217; uh?' ) );
			check_admin_referer( 'cws-age-verification-update-settings' );
			update_option( 'cws_age_verification_age',             absint( $_POST['cws-age-setting'] )              );
			update_option( 'cws_age_verification_timeout',         absint( $_POST['cws-timeout-setting'] )          );
			update_option( 'cws_age_verification_skip_registered', ( $_POST['cws-registered-setting'] ) ? '1' : '0' );
			update_option( 'cws_age_verification_use_dropdowns',   ( $_POST['cws-dropdown-setting'] ) ? '1' : '0'   );
		}
		if ( !empty($_POST ) ) { ?>
		<div id="message" class="updated fade"><p><strong><?php _e( 'Cambios guardados.' ) ?></strong></p></div>
		<?php } ?>
		<div class="wrap">
			<h2>Configuraciones del Verificador de Edad</h2>
				<form action="" method="post" id="age-verification-settings">
					<?php wp_nonce_field( 'cws-age-verification-update-settings' ); ?>
					<p>Los usuarios deben tener por lo menos <input style="width:2em;" type="text" name="cws-age-setting" value="<?php echo attribute_escape( cws_age_verification::age_required() ); ?>" maxlength="2" /> años para entrar al sitio web, y tendrán que volver a verificar su edad después de <input style="width:4em;" type="text" name="cws-timeout-setting" value="<?php echo attribute_escape( cws_age_verification::timeout_minutes() ); ?>" maxlength="4" /> minutos sin actividad.</p>
					
					<p><input type="checkbox" <?php checked( get_option( 'cws_age_verification_skip_registered' ), '1' ); ?> value="1" name="cws-registered-setting" id="cws-registered-setting" /> <label for="cws-registered-setting">Usuarios loggeados en el sitio no tienen que verificar su edad.</label></p>
					
					<p>Selecciona una opción para los inputs del verificador de edad <select name="cws-dropdown-setting"><option value="1" <?php selected( get_option( 'cws_age_verification_use_dropdowns' ), '1' ); ?>>Menú desplegable</option><option value="0" <?php selected( get_option( 'cws_age_verification_use_dropdowns' ), '0' ); ?>>Texto plano</option></select></p>
					
					<p class="submit"><input type="submit" value="Guardar Cambios &raquo;" /></p>
				</form>
		</div>
<?php
	}

}

function cws_age_verification_admin() {
	add_option( 'cws_age_verification_age',             '13' ); // default to 13 because of COPPA
	add_option( 'cws_age_verification_timeout',         '60' ); // one hour
	add_option( 'cws_age_verification_skip_registered', '1'  );
	add_option( 'cws_age_verification_use_dropdowns',   '1'  );
	if ( function_exists( 'add_submenu_page' ) )
		add_submenu_page( 'plugins.php', 'Age Verification', 'Configuración del Verificador de Edad', 'manage_options', 'age-verification', array( 'cws_age_verification', 'admin' ) );
}

add_action( 'init', create_function( '$a', "add_action( 'admin_menu', 'cws_age_verification_admin' );" ) );
add_action( 'template_redirect', array( 'cws_age_verification', 'check' ) );

?>
