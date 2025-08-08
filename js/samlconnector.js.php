<?php
/* Copyright (C) 2022 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if(! defined('NOREQUIREUSER')) define('NOREQUIREUSER', 1);
if(! defined('NOREQUIREDB')) define('NOREQUIREDB', 1);
if(! defined('NOREQUIRESOC')) define('NOREQUIRESOC', 1);
if(! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN', 1);
if(! defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if(! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if(! defined('NOLOGIN')) define('NOLOGIN', 1);
if(! defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if(! defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if(! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', 1);

// Load Dolibarr environment
$res = 0;
$main_inc = 'main.inc.php';
for($i = 0 ; $i < 5 && ! $res ; $i++) $res = @include str_repeat('../', $i).$main_inc;

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');
?>

function copyToClipBoardCommand(value) {
    navigator.clipboard.writeText(value);
}

function copyToClipBoardAnimation(el, cpResult = true) {
    let checkedIconClass = 'fa fa-check';
    let errorIconClass = 'fa fa-exclamation-triangle';

    let iconEl = el.find('.fa-clipboard, .fa-check, .fa-exclamation-triangle');
    let idleIconClass = 'fa fa-clipboard';

    iconEl.slideUp(200, function() {
        if(cpResult) {
            iconEl.attr('class', checkedIconClass);
        }
        else {
            iconEl.attr('class', errorIconClass);
        }

        iconEl.slideDown(200).delay(1200).slideUp(200, function() {
            iconEl.attr('class', idleIconClass);
            iconEl.slideDown(200);
        });
    });
}

$(document).ready(function() {
    $('body').on('click', '.samlCopyClipboard', function() {
        copyToClipBoardCommand($(this).find('span').text());
        copyToClipBoardAnimation($(this));
    });

// Sélecteur pour les deux états du bouton (on/off)
	// On cible une classe commune 'ajax-toggle' qu'on ajoutera dans le PHP
	$('#row_toggle_create_user').on('click', function() {
		// Le nom de la constante que le bouton modifie
		const code = 'SAMLCONNECTOR_CREATE_UNEXISTING_USER';
		// On attache un écouteur de clic sur le document entier.
		// Il s'activera SEULEMENT si l'élément cliqué est notre bouton "set".
		// C'est la méthode la plus fiable.
		$(document).on('click', '#set_' + code, function() {
			// Le bouton "set" est cliqué (on active), donc ON AFFICHE les lignes.
			$('#row_default_group, #row_default_entity').show();
		});
		// On fait de même pour le bouton "del".
		$(document).on('click', '#del_' + code, function() {
			// Le bouton "del" est cliqué (on désactive), donc ON CACHE les lignes.
			$('#row_default_group, #row_default_entity').hide();
		});

	});
});
