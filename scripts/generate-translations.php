<?php
/**
 * Generate .po and .mo translation files for MSC Stealth Login.
 *
 * Usage: php scripts/generate-translations.php
 *
 * Requires: msgfmt (from gettext package) in PATH.
 *
 * @package MSCSL
 */

$plugin_dir = dirname( __DIR__ );
$lang_dir   = $plugin_dir . '/languages';
$pot_file   = $lang_dir . '/msc-stealth-login.pot';

if ( ! file_exists( $pot_file ) ) {
	echo "Error: POT file not found at {$pot_file}\n";
	exit( 1 );
}

// Translation dictionaries for each locale.
// Key = English msgid, Value = translated msgstr.
// Only non-empty translations are included — empty entries use the POT default.
$translations = array(
	// -----------------------------------------------------------------
	// German (Germany)
	// -----------------------------------------------------------------
	'de_DE' => array(
		'language'     => 'de_DE',
		'language_name' => 'German',
		'plural_forms' => 'nplurals=2; plural=(n != 1);',
		'strings'      => array(
			'MSC Stealth Login' => 'MSC Stealth Login',
			'Hide your login page, block brute force attacks, and protect your WordPress site from unauthorized access. Complete free plugin with all features included.' => 'Verstecken Sie Ihre Anmeldeseite, blockieren Sie Brute-Force-Angriffe und schützen Sie Ihre WordPress-Website vor unbefugtem Zugriff. Vollständiges kostenloses Plugin mit allen Funktionen.',
			'Anomalous Developers' => 'Anomalous Developers',
			'The following plugins were detected: %s. You may need to configure cache or security exclusions for your custom login URL.' => 'Die folgenden Plugins wurden erkannt: %s. Möglicherweise müssen Sie Cache- oder Sicherheitsausnahmen für Ihre benutzerdefinierte Anmelde-URL konfigurieren.',
			'You do not have permission to access this page.' => 'Sie haben keine Berechtigung, auf diese Seite zuzugreifen.',
			'Pingback functionality is disabled.' => 'Die Pingback-Funktionalität ist deaktiviert.',
			'Too many failed login attempts. Please try again in %d minutes.' => 'Zu viele fehlgeschlagene Anmeldeversuche. Bitte versuchen Sie es in %d Minuten erneut.',
			'Too many failed login attempts. You are temporarily blocked for %1$d days and %2$d hours.' => 'Zu viele fehlgeschlagene Anmeldeversuche. Sie sind vorübergehend für %1$d Tage und %2$d Stunden gesperrt.',
			'Too many failed login attempts. You are temporarily blocked for %1$d hours and %2$d minutes.' => 'Zu viele fehlgeschlagene Anmeldeversuche. Sie sind vorübergehend für %1$d Stunden und %2$d Minuten gesperrt.',
			'[%s] Brute Force Lockout Alert' => '[%s] Brute-Force-Sperrungswarnung',
			"A brute force lockout has occurred on %1\$s.\n\nIP Address: %2\$s\nFailed Attempts: %3\$d\nTime: %4\$s\n\nThis IP has been temporarily blocked from making login attempts.\n\nIf this is not expected behavior, you may want to investigate this IP address." => "Eine Brute-Force-Sperrung ist auf %1\$s aufgetreten.\n\nIP-Adresse: %2\$s\nFehlgeschlagene Versuche: %3\$d\nZeit: %4\$s\n\nDiese IP wurde vorübergehend für Anmeldeversuche gesperrt.\n\nWenn dies kein erwartetes Verhalten ist, sollten Sie diese IP-Adresse untersuchen.",
			'Locked Out' => 'Gesperrt',
			'Access Denied' => 'Zugriff verweigert',
			'The login page you are trying to access has been hidden for security reasons. Please use the correct login URL to access this site.' => 'Die Anmeldeseite, auf die Sie zugreifen möchten, wurde aus Sicherheitsgründen versteckt. Bitte verwenden Sie die korrekte Anmelde-URL.',
			'[%s] Your Login URL Recovery' => '[%s] Wiederherstellung Ihrer Anmelde-URL',
			'You requested a login URL recovery for %1$s. Your stealth login URL is: %2$s' => 'Sie haben eine Wiederherstellung der Anmelde-URL für %1$s angefordert. Ihre Stealth-Anmelde-URL lautet: %2$s',
			'Recovery email sent successfully.' => 'Wiederherstellungs-E-Mail erfolgreich gesendet.',
			'Failed to send recovery email. Please check the email address.' => 'Wiederherstellungs-E-Mail konnte nicht gesendet werden. Bitte überprüfen Sie die E-Mail-Adresse.',
			'Failed to send recovery email.' => 'Wiederherstellungs-E-Mail konnte nicht gesendet werden.',
			'[%s] User Login Alert' => '[%s] Benutzer-Anmeldewarnung',
			"User: %1\$s\nIP Address: %2\$s\nTime: %3\$s\n\nThis is an automated alert from your WordPress security plugin." => "Benutzer: %1\$s\nIP-Adresse: %2\$s\nZeit: %3\$s\n\nDies ist eine automatische Warnung von Ihrem WordPress-Sicherheits-Plugin.",
			'[%s] New Login Location Detected' => '[%s] Neuer Anmeldestandort erkannt',
			"Hello %1\$s,\n\nWe detected a login to your account from a new IP address:\n\nIP Address: %2\$s\nTime: %3\$s\n\nIf this was not you, please contact your site administrator immediately." => "Hallo %1\$s,\n\nWir haben eine Anmeldung bei Ihrem Konto von einer neuen IP-Adresse erkannt:\n\nIP-Adresse: %2\$s\nZeit: %3\$s\n\nWenn Sie das nicht waren, kontaktieren Sie bitte sofort Ihren Website-Administrator.",
			'IP Address' => 'IP-Adresse',
			'Username' => 'Benutzername',
			'Result' => 'Ergebnis',
			'User Agent' => 'User-Agent',
			'Date/Time' => 'Datum/Uhrzeit',
			'Settings updated successfully.' => 'Einstellungen erfolgreich aktualisiert.',
			'Settings' => 'Einstellungen',
			'Advanced' => 'Erweitert',
			'Email' => 'E-Mail',
			'History' => 'Verlauf',
			'Support' => 'Unterstützung',
			'Enable Stealth Login' => 'Stealth-Anmeldung aktivieren',
			'Enable stealth login protection' => 'Stealth-Anmeldeschutz aktivieren',
			'Custom Login URL' => 'Benutzerdefinierte Anmelde-URL',
			'Your login page will be at: %s' => 'Ihre Anmeldeseite wird unter: %s erreichbar sein',
			'Hide wp-admin' => 'wp-admin verstecken',
			'Block direct access to wp-admin directory' => 'Direkten Zugriff auf das wp-admin-Verzeichnis blockieren',
			'Users will be redirected to your custom login URL or the specified URL when trying to access wp-admin directly.' => 'Benutzer werden auf Ihre benutzerdefinierte Anmelde-URL oder die angegebene URL umgeleitet, wenn sie versuchen, direkt auf wp-admin zuzugreifen.',
			'wp-admin Redirect URL' => 'wp-admin Weiterleitungs-URL',
			'Where to redirect users when they try to access wp-admin directly. Defaults to homepage.' => 'Wohin Benutzer umgeleitet werden, wenn sie versuchen, direkt auf wp-admin zuzugreifen. Standard ist die Startseite.',
			'Logout Redirect URL' => 'Abmelde-Weiterleitungs-URL',
			'Where to redirect users after logging out. Defaults to homepage.' => 'Wohin Benutzer nach dem Abmelden umgeleitet werden. Standard ist die Startseite.',
			'Login URL Preview' => 'Anmelde-URL Vorschau',
			'This is what your custom login URL will look like.' => 'So wird Ihre benutzerdefinierte Anmelde-URL aussehen.',
			'Emergency Recovery URL' => 'Notfall-Wiederherstellungs-URL',
			'Current Recovery URL:' => 'Aktuelle Wiederherstellungs-URL:',
			'Copy URL' => 'URL kopieren',
			'Copied!' => 'Kopiert!',
			'Bookmark this URL! If you lose access to your custom login URL, use this recovery URL to access wp-login.php and then navigate to settings to find your custom URL.' => 'Setzen Sie ein Lesezeichen für diese URL! Wenn Sie den Zugriff auf Ihre benutzerdefinierte Anmelde-URL verlieren, verwenden Sie diese Wiederherstellungs-URL, um auf wp-login.php zuzugreifen.',
			'Regenerate Recovery URL' => 'Wiederherstellungs-URL neu generieren',
			'Are you sure? This will invalidate the current recovery URL.' => 'Sind Sie sicher? Dadurch wird die aktuelle Wiederherstellungs-URL ungültig.',
			'Recovery URL regenerated successfully!' => 'Wiederherstellungs-URL erfolgreich neu generiert!',
			'Save Settings' => 'Einstellungen speichern',
			'Advanced security features provide additional protection against brute force attacks, XML-RPC exploitation, and user enumeration via the REST API. These features are optional and may affect compatibility with some plugins or services that require XML-RPC or REST API access.' => 'Erweiterte Sicherheitsfunktionen bieten zusätzlichen Schutz gegen Brute-Force-Angriffe, XML-RPC-Ausnutzung und Benutzeraufzählung über die REST-API. Diese Funktionen sind optional und können die Kompatibilität mit einigen Plugins oder Diensten beeinträchtigen.',
			'Enable Advanced Security Features' => 'Erweiterte Sicherheitsfunktionen aktivieren',
			'Enable additional security features including brute force protection, XML-RPC blocking, and REST API protection.' => 'Aktivieren Sie zusätzliche Sicherheitsfunktionen einschließlich Brute-Force-Schutz, XML-RPC-Blockierung und REST-API-Schutz.',
			'When disabled, only the basic stealth login features will be active. Enable this to protect against automated attacks.' => 'Wenn deaktiviert, sind nur die grundlegenden Stealth-Anmeldefunktionen aktiv. Aktivieren Sie dies zum Schutz vor automatisierten Angriffen.',
			'XML-RPC & REST API Protection' => 'XML-RPC & REST-API-Schutz',
			'Disable XML-RPC' => 'XML-RPC deaktivieren',
			'Block XML-RPC requests' => 'XML-RPC-Anfragen blockieren',
			'Prevents XML-RPC attacks and pingbacks. This also blocks the XML-RPC authentication method. Only available when Advanced Security is enabled.' => 'Verhindert XML-RPC-Angriffe und Pingbacks. Dies blockiert auch die XML-RPC-Authentifizierungsmethode. Nur verfügbar, wenn Erweiterte Sicherheit aktiviert ist.',
			'Disable REST API User Enumeration' => 'REST-API-Benutzeraufzählung deaktivieren',
			'Block REST API user enumeration' => 'REST-API-Benutzeraufzählung blockieren',
			'Prevents attackers from discovering usernames via the REST API by querying ?author=1. Only available when Advanced Security is enabled.' => 'Verhindert, dass Angreifer Benutzernamen über die REST-API durch Abfrage von ?author=1 entdecken. Nur verfügbar, wenn Erweiterte Sicherheit aktiviert ist.',
			'Brute Force Protection' => 'Brute-Force-Schutz',
			'Enable Brute Force Protection' => 'Brute-Force-Schutz aktivieren',
			'Enable login attempt limiting' => 'Anmeldeversuch-Begrenzung aktivieren',
			'Limits login attempts to prevent brute force attacks. Only available when Advanced Security is enabled.' => 'Begrenzt Anmeldeversuche, um Brute-Force-Angriffe zu verhindern. Nur verfügbar, wenn Erweiterte Sicherheit aktiviert ist.',
			'Max Login Attempts' => 'Maximale Anmeldeversuche',
			'Number of failed attempts before a temporary lockout (1-10).' => 'Anzahl fehlgeschlagener Versuche vor einer vorübergehenden Sperrung (1-10).',
			'Lockout Duration' => 'Sperrdauer',
			'minutes' => 'Minuten',
			'How long the lockout lasts in minutes (5-60).' => 'Wie lange die Sperrung in Minuten dauert (5-60).',
			'Login Logging' => 'Anmeldeprotokollierung',
			'Enable Login Logging' => 'Anmeldeprotokollierung aktivieren',
			'Log all login attempts to database' => 'Alle Anmeldeversuche in der Datenbank protokollieren',
			'When enabled, all login attempts (success, failure, lockout) will be recorded in the database for review in the History tab.' => 'Wenn aktiviert, werden alle Anmeldeversuche (Erfolg, Fehlschlag, Sperrung) in der Datenbank für die Überprüfung im Verlaufs-Tab aufgezeichnet.',
			'IP Whitelist' => 'IP-Whitelist',
			'Whitelisted IP Addresses' => 'Freigeschaltete IP-Adressen',
			'Enter IP addresses that should bypass brute force protection. Enter one IP per line or separate with commas. Examples:' => 'Geben Sie IP-Adressen ein, die den Brute-Force-Schutz umgehen sollen. Geben Sie eine IP pro Zeile ein oder trennen Sie sie mit Kommas. Beispiele:',
			'Progressive Lockout' => 'Progressive Sperrung',
			'Progressive Lockout Delays' => 'Progressive Sperrverzögerungen',
			'Enable progressive lockout delays' => 'Progressive Sperrverzögerungen aktivieren',
			'Each subsequent lockout doubles the wait time. Helps stop persistent attackers. Resets after 24 hours of no attempts.' => 'Jede nachfolgende Sperrung verdoppelt die Wartezeit. Hilft, hartnäckige Angreifer zu stoppen. Wird nach 24 Stunden ohne Versuche zurückgesetzt.',
			'Maximum Lockout Duration' => 'Maximale Sperrdauer',
			'Maximum lockout duration even with progressive delays (60-1440 minutes).' => 'Maximale Sperrdauer auch bei progressiven Verzögerungen (60-1440 Minuten).',
			'Enable Email Notifications' => 'E-Mail-Benachrichtigungen aktivieren',
			'Enable email notification features' => 'E-Mail-Benachrichtigungsfunktionen aktivieren',
			'Master toggle for all email notifications. When enabled, you can configure individual notification types below.' => 'Hauptschalter für alle E-Mail-Benachrichtigungen. Wenn aktiviert, können Sie einzelne Benachrichtigungstypen unten konfigurieren.',
			'Configure email notifications for login lockouts and alerts. These settings are independent of the Advanced Security features and can be enabled separately.' => 'Konfigurieren Sie E-Mail-Benachrichtigungen für Anmeldesperrungen und Warnungen. Diese Einstellungen sind unabhängig von den erweiterten Sicherheitsfunktionen.',
			'Lockout Notifications' => 'Sperrungsbenachrichtigungen',
			'Lockout Email Notification' => 'Sperrungs-E-Mail-Benachrichtigung',
			'Send email when a lockout occurs' => 'E-Mail senden, wenn eine Sperrung auftritt',
			'Notification Email' => 'Benachrichtigungs-E-Mail',
			'Email address to receive lockout notifications. Defaults to site admin email.' => 'E-Mail-Adresse für Sperrungsbenachrichtigungen. Standard ist die Admin-E-Mail.',
			'Email Subject' => 'E-Mail-Betreff',
			'Custom email subject. Leave blank for default.' => 'Benutzerdefinierter E-Mail-Betreff. Leer lassen für Standard.',
			'Email Body' => 'E-Mail-Text',
			'Custom email body. Leave blank for default. Available placeholders:' => 'Benutzerdefinierter E-Mail-Text. Leer lassen für Standard. Verfügbare Platzhalter:',
			'Number of failed attempts' => 'Anzahl fehlgeschlagener Versuche',
			'Current time' => 'Aktuelle Uhrzeit',
			'Site name' => 'Website-Name',
			'Site URL' => 'Website-URL',
			'Login Alerts' => 'Anmeldewarnungen',
			'Admin Login Alert' => 'Admin-Anmeldewarnung',
			'Send email to admin when any user logs in' => 'E-Mail an Admin senden, wenn sich ein Benutzer anmeldet',
			'New IP Login Alert' => 'Neue IP-Anmeldewarnung',
			'Email user when they login from a new IP address' => 'Benutzer per E-Mail benachrichtigen, wenn sie sich von einer neuen IP-Adresse anmelden',
			'Users will be notified when their account is accessed from an IP address they have not used before.' => 'Benutzer werden benachrichtigt, wenn auf ihr Konto von einer IP-Adresse zugegriffen wird, die sie noch nicht verwendet haben.',
			'Login Attempt History' => 'Verlauf der Anmeldeversuche',
			'Filter by IP' => 'Nach IP filtern',
			'Filter by Username' => 'Nach Benutzername filtern',
			'Filter by Result' => 'Nach Ergebnis filtern',
			'All Results' => 'Alle Ergebnisse',
			'Success' => 'Erfolgreich',
			'Failed' => 'Fehlgeschlagen',
			'Locked Out' => 'Gesperrt',
			'Whitelisted' => 'Freigeschaltet',
			'Date From' => 'Datum von',
			'Date To' => 'Datum bis',
			'Filter' => 'Filtern',
			'Clear Filters' => 'Filter löschen',
			'No login attempts recorded yet.' => 'Noch keine Anmeldeversuche aufgezeichnet.',
			'Showing %1$d to %2$d of %3$d entries' => 'Zeige %1$d bis %2$d von %3$d Einträgen',
			'%d items' => '%d Einträge',
			'&laquo; Previous' => '&laquo; Zurück',
			'Next &raquo;' => 'Weiter &raquo;',
			'Clear All Logs' => 'Alle Protokolle löschen',
			'Are you sure you want to clear all login logs?' => 'Sind Sie sicher, dass Sie alle Anmeldeprotokolle löschen möchten?',
			'Export to CSV' => 'Als CSV exportieren',
			'All login logs have been cleared.' => 'Alle Anmeldeprotokolle wurden gelöscht.',
			'How to Use MSC Stealth Login' => 'So verwenden Sie MSC Stealth Login',
			'MSC Stealth Login helps secure your WordPress site by hiding the default login page and adding brute force protection.' => 'MSC Stealth Login sichert Ihre WordPress-Website, indem es die Standard-Anmeldeseite versteckt und Brute-Force-Schutz hinzufügt.',
			'Setting Up Your Custom Login URL' => 'Einrichten Ihrer benutzerdefinierten Anmelde-URL',
			'Go to the Settings tab above.' => 'Gehen Sie zum Einstellungen-Tab oben.',
			'Enter a custom login slug (e.g., "my-secret-login" or "admin-access").' => 'Geben Sie einen benutzerdefinierten Anmelde-Slug ein (z.B. "my-secret-login" oder "admin-access").',
			'Click Save Settings.' => 'Klicken Sie auf Einstellungen speichern.',
			'Your new login URL will be:' => 'Ihre neue Anmelde-URL wird sein:',
			'Bookmark this URL immediately!' => 'Setzen Sie sofort ein Lesezeichen für diese URL!',
			'Important:' => 'Wichtig:',
			'You will no longer be able to access wp-login.php directly.' => 'Sie können nicht mehr direkt auf wp-login.php zugreifen.',
			'Security Features Explained' => 'Sicherheitsfunktionen erklärt',
			'Changes your login page from /wp-login.php to a custom URL of your choice. This prevents automated bots from finding your login page.' => 'Ändert Ihre Anmeldeseite von /wp-login.php in eine benutzerdefinierte URL Ihrer Wahl. Dies verhindert, dass automatisierte Bots Ihre Anmeldeseite finden.',
			'Blocks direct access to the /wp-admin/ directory and redirects users to your custom login URL. Prevents unauthorized access attempts.' => 'Blockiert den direkten Zugriff auf das /wp-admin/-Verzeichnis und leitet Benutzer auf Ihre benutzerdefinierte Anmelde-URL um.',
			'Blocks XML-RPC requests which are commonly used for brute force attacks and pingback spam. If you use mobile apps or external services that require XML-RPC, leave this disabled.' => 'Blockiert XML-RPC-Anfragen, die häufig für Brute-Force-Angriffe und Pingback-Spam verwendet werden. Wenn Sie mobile Apps oder externe Dienste verwenden, die XML-RPC erfordern, lassen Sie dies deaktiviert.',
			'Prevents attackers from discovering usernames by querying the REST API with ?author=1, ?author=2, etc.' => 'Verhindert, dass Angreifer Benutzernamen durch Abfrage der REST-API mit ?author=1, ?author=2 usw. entdecken.',
			'Limits login attempts to prevent brute force attacks. After the configured number of failed attempts, the IP address is temporarily blocked.' => 'Begrenzt Anmeldeversuche, um Brute-Force-Angriffe zu verhindern. Nach der konfigurierten Anzahl fehlgeschlagener Versuche wird die IP-Adresse vorübergehend gesperrt.',
			'A special URL that allows you to access wp-login.php even when the plugin is active. This is your safety net if you forget your custom login URL. Bookmark this URL and keep it safe!' => 'Eine spezielle URL, mit der Sie auf wp-login.php zugreifen können, auch wenn das Plugin aktiv ist. Dies ist Ihr Sicherheitsnetz. Setzen Sie ein Lesezeichen und bewahren Sie es sicher auf!',
			'Frequently Asked Questions' => 'Häufig gestellte Fragen',
			'What happens if I forget my custom login URL?' => 'Was passiert, wenn ich meine benutzerdefinierte Anmelde-URL vergesse?',
			'Use the Emergency Recovery URL shown on the Settings tab. Bookmark this URL when you first set up the plugin. If you lose both URLs, you will need to disable the plugin via FTP or WP-CLI to regain access.' => 'Verwenden Sie die Notfall-Wiederherstellungs-URL auf dem Einstellungen-Tab. Setzen Sie ein Lesezeichen bei der Ersteinrichtung. Wenn Sie beide URLs verlieren, müssen Sie das Plugin per FTP oder WP-CLI deaktivieren.',
			'Will this break my site?' => 'Wird dies meine Website beschädigen?',
			'If configured correctly, no. However, we strongly recommend testing on a staging site first. Always bookmark your custom login URL and the recovery URL before logging out.' => 'Wenn richtig konfiguriert, nein. Wir empfehlen jedoch dringend, zunächst auf einer Staging-Website zu testen. Setzen Sie immer Lesezeichen für Ihre Anmelde-URL und die Wiederherstellungs-URL.',
			'Can I still use wp-admin after logging in?' => 'Kann ich nach der Anmeldung weiterhin wp-admin verwenden?',
			'Yes! Once you are logged in, wp-admin works normally. The "Hide wp-admin" feature only blocks direct access for non-logged-in users.' => 'Ja! Sobald Sie angemeldet sind, funktioniert wp-admin normal. Die Funktion "wp-admin verstecken" blockiert nur den direkten Zugriff für nicht angemeldete Benutzer.',
			'Will this work with my security plugin?' => 'Funktioniert dies mit meinem Sicherheits-Plugin?',
			'Generally yes, but avoid using multiple plugins that modify login URLs or block wp-admin simultaneously as they may conflict.' => 'Im Allgemeinen ja, aber vermeiden Sie die gleichzeitige Verwendung mehrerer Plugins, die Anmelde-URLs ändern oder wp-admin blockieren.',
			'Does this work with WooCommerce?' => 'Funktioniert dies mit WooCommerce?',
			'Yes, but note that WooCommerce has its own login/my-account pages. You may need to test compatibility with your specific setup.' => 'Ja, aber beachten Sie, dass WooCommerce eigene Anmelde-/Kontoseiten hat. Möglicherweise müssen Sie die Kompatibilität mit Ihrem Setup testen.',
			'How do I disable the plugin if I get locked out?' => 'Wie deaktiviere ich das Plugin, wenn ich ausgesperrt werde?',
			'Use the Emergency Recovery URL (if you have it bookmarked)' => 'Verwenden Sie die Notfall-Wiederherstellungs-URL (falls Sie ein Lesezeichen gesetzt haben)',
			'Via FTP/SFTP: Rename the plugin folder from /wp-content/plugins/msc-stealth-login/ to /wp-content/plugins/msc-stealth-login-disabled/' => 'Per FTP/SFTP: Benennen Sie den Plugin-Ordner von /wp-content/plugins/msc-stealth-login/ in /wp-content/plugins/msc-stealth-login-disabled/ um',
			'Via WP-CLI: Run: wp plugin deactivate msc-stealth-login' => 'Per WP-CLI: Ausführen: wp plugin deactivate msc-stealth-login',
			'Via Database: Set the mscsl_options option value to have module_enabled = 0' => 'Per Datenbank: Setzen Sie den mscsl_options-Optionswert auf module_enabled = 0',
			'Need Help?' => 'Brauchen Sie Hilfe?',
			"If you have questions, encounter bugs, or need setup assistance, we're here to help." => 'Wenn Sie Fragen haben, Fehler finden oder Hilfe bei der Einrichtung benötigen, sind wir für Sie da.',
			'Get Support' => 'Unterstützung erhalten',
		),
	),

	// -----------------------------------------------------------------
	// German (Switzerland) — identical to de_DE with minor regional phrasing
	// -----------------------------------------------------------------
	'de_CH' => array(
		'language'     => 'de_CH',
		'language_name' => 'German (Switzerland)',
		'plural_forms' => 'nplurals=2; plural=(n != 1);',
		'strings'      => 'de_DE', // inherit from de_DE
	),

	// -----------------------------------------------------------------
	// Spanish (Spain)
	// -----------------------------------------------------------------
	'es_ES' => array(
		'language'     => 'es_ES',
		'language_name' => 'Spanish (Spain)',
		'plural_forms' => 'nplurals=2; plural=(n != 1);',
		'strings'      => array(
			'MSC Stealth Login' => 'MSC Stealth Login',
			'Hide your login page, block brute force attacks, and protect your WordPress site from unauthorized access. Complete free plugin with all features included.' => 'Oculta tu página de inicio de sesión, bloquea ataques de fuerza bruta y protege tu sitio WordPress contra accesos no autorizados. Plugin gratuito completo con todas las funciones.',
			'Anomalous Developers' => 'Anomalous Developers',
			'The following plugins were detected: %s. You may need to configure cache or security exclusions for your custom login URL.' => 'Se detectaron los siguientes plugins: %s. Es posible que debas configurar exclusiones de caché o seguridad para tu URL de inicio de sesión personalizada.',
			'You do not have permission to access this page.' => 'No tienes permiso para acceder a esta página.',
			'Pingback functionality is disabled.' => 'La funcionalidad de pingback está desactivada.',
			'Too many failed login attempts. Please try again in %d minutes.' => 'Demasiados intentos de inicio de sesión fallidos. Por favor, inténtalo de nuevo en %d minutos.',
			'Too many failed login attempts. You are temporarily blocked for %1$d days and %2$d hours.' => 'Demasiados intentos de inicio de sesión fallidos. Estás bloqueado temporalmente durante %1$d días y %2$d horas.',
			'Too many failed login attempts. You are temporarily blocked for %1$d hours and %2$d minutes.' => 'Demasiados intentos de inicio de sesión fallidos. Estás bloqueado temporalmente durante %1$d horas y %2$d minutos.',
			'[%s] Brute Force Lockout Alert' => '[%s] Alerta de bloqueo por fuerza bruta',
			'Locked Out' => 'Bloqueado',
			'Access Denied' => 'Acceso denegado',
			'The login page you are trying to access has been hidden for security reasons. Please use the correct login URL to access this site.' => 'La página de inicio de sesión a la que intentas acceder ha sido ocultada por razones de seguridad. Por favor, usa la URL de inicio de sesión correcta.',
			'[%s] Your Login URL Recovery' => '[%s] Recuperación de tu URL de inicio de sesión',
			'You requested a login URL recovery for %1$s. Your stealth login URL is: %2$s' => 'Has solicitado una recuperación de URL de inicio de sesión para %1$s. Tu URL de inicio de sesión sigilosa es: %2$s',
			'Recovery email sent successfully.' => 'Correo de recuperación enviado correctamente.',
			'Failed to send recovery email. Please check the email address.' => 'No se pudo enviar el correo de recuperación. Por favor, verifica la dirección de correo.',
			'Failed to send recovery email.' => 'No se pudo enviar el correo de recuperación.',
			'[%s] User Login Alert' => '[%s] Alerta de inicio de sesión de usuario',
			'[%s] New Login Location Detected' => '[%s] Nueva ubicación de inicio de sesión detectada',
			'IP Address' => 'Dirección IP',
			'Username' => 'Nombre de usuario',
			'Result' => 'Resultado',
			'User Agent' => 'Agente de usuario',
			'Date/Time' => 'Fecha/Hora',
			'Settings updated successfully.' => 'Configuración actualizada correctamente.',
			'Settings' => 'Configuración',
			'Advanced' => 'Avanzado',
			'Email' => 'Correo electrónico',
			'History' => 'Historial',
			'Support' => 'Soporte',
			'Enable Stealth Login' => 'Activar inicio de sesión sigiloso',
			'Enable stealth login protection' => 'Activar protección de inicio de sesión sigiloso',
			'Custom Login URL' => 'URL de inicio de sesión personalizada',
			'Your login page will be at: %s' => 'Tu página de inicio de sesión estará en: %s',
			'Hide wp-admin' => 'Ocultar wp-admin',
			'Block direct access to wp-admin directory' => 'Bloquear acceso directo al directorio wp-admin',
			'Users will be redirected to your custom login URL or the specified URL when trying to access wp-admin directly.' => 'Los usuarios serán redirigidos a tu URL de inicio de sesión personalizada o la URL especificada al intentar acceder directamente a wp-admin.',
			'wp-admin Redirect URL' => 'URL de redirección de wp-admin',
			'Where to redirect users when they try to access wp-admin directly. Defaults to homepage.' => 'A dónde redirigir a los usuarios cuando intentan acceder directamente a wp-admin. Por defecto, la página de inicio.',
			'Logout Redirect URL' => 'URL de redirección al cerrar sesión',
			'Where to redirect users after logging out. Defaults to homepage.' => 'A dónde redirigir a los usuarios después de cerrar sesión. Por defecto, la página de inicio.',
			'Login URL Preview' => 'Vista previa de la URL de inicio de sesión',
			'This is what your custom login URL will look like.' => 'Así se verá tu URL de inicio de sesión personalizada.',
			'Emergency Recovery URL' => 'URL de recuperación de emergencia',
			'Current Recovery URL:' => 'URL de recuperación actual:',
			'Copy URL' => 'Copiar URL',
			'Copied!' => '¡Copiado!',
			'Bookmark this URL! If you lose access to your custom login URL, use this recovery URL to access wp-login.php and then navigate to settings to find your custom URL.' => '¡Guarda esta URL en marcadores! Si pierdes acceso a tu URL personalizada, usa esta URL de recuperación para acceder a wp-login.php.',
			'Regenerate Recovery URL' => 'Regenerar URL de recuperación',
			'Are you sure? This will invalidate the current recovery URL.' => '¿Estás seguro? Esto invalidará la URL de recuperación actual.',
			'Recovery URL regenerated successfully!' => '¡URL de recuperación regenerada correctamente!',
			'Save Settings' => 'Guardar configuración',
			'Advanced security features provide additional protection against brute force attacks, XML-RPC exploitation, and user enumeration via the REST API. These features are optional and may affect compatibility with some plugins or services that require XML-RPC or REST API access.' => 'Las funciones de seguridad avanzada proporcionan protección adicional contra ataques de fuerza bruta, explotación XML-RPC y enumeración de usuarios a través de la API REST. Estas funciones son opcionales y pueden afectar la compatibilidad.',
			'Enable Advanced Security Features' => 'Activar funciones de seguridad avanzada',
			'Enable additional security features including brute force protection, XML-RPC blocking, and REST API protection.' => 'Activar funciones de seguridad adicionales incluyendo protección contra fuerza bruta, bloqueo XML-RPC y protección de la API REST.',
			'When disabled, only the basic stealth login features will be active. Enable this to protect against automated attacks.' => 'Cuando está desactivado, solo las funciones básicas de inicio de sesión sigiloso estarán activas. Activa esto para protegerte contra ataques automatizados.',
			'XML-RPC & REST API Protection' => 'Protección XML-RPC y API REST',
			'Disable XML-RPC' => 'Desactivar XML-RPC',
			'Block XML-RPC requests' => 'Bloquear solicitudes XML-RPC',
			'Prevents XML-RPC attacks and pingbacks. This also blocks the XML-RPC authentication method. Only available when Advanced Security is enabled.' => 'Previene ataques XML-RPC y pingbacks. Esto también bloquea el método de autenticación XML-RPC. Solo disponible cuando la Seguridad Avanzada está activada.',
			'Disable REST API User Enumeration' => 'Desactivar enumeración de usuarios de la API REST',
			'Block REST API user enumeration' => 'Bloquear enumeración de usuarios de la API REST',
			'Prevents attackers from discovering usernames via the REST API by querying ?author=1. Only available when Advanced Security is enabled.' => 'Previene que los atacantes descubran nombres de usuario a través de la API REST consultando ?author=1. Solo disponible cuando la Seguridad Avanzada está activada.',
			'Brute Force Protection' => 'Protección contra fuerza bruta',
			'Enable Brute Force Protection' => 'Activar protección contra fuerza bruta',
			'Enable login attempt limiting' => 'Activar limitación de intentos de inicio de sesión',
			'Limits login attempts to prevent brute force attacks. Only available when Advanced Security is enabled.' => 'Limita los intentos de inicio de sesión para prevenir ataques de fuerza bruta. Solo disponible cuando la Seguridad Avanzada está activada.',
			'Max Login Attempts' => 'Máximo de intentos de inicio de sesión',
			'Number of failed attempts before a temporary lockout (1-10).' => 'Número de intentos fallidos antes de un bloqueo temporal (1-10).',
			'Lockout Duration' => 'Duración del bloqueo',
			'minutes' => 'minutos',
			'How long the lockout lasts in minutes (5-60).' => 'Cuánto dura el bloqueo en minutos (5-60).',
			'Login Logging' => 'Registro de inicios de sesión',
			'Enable Login Logging' => 'Activar registro de inicios de sesión',
			'Log all login attempts to database' => 'Registrar todos los intentos de inicio de sesión en la base de datos',
			'When enabled, all login attempts (success, failure, lockout) will be recorded in the database for review in the History tab.' => 'Cuando está activado, todos los intentos de inicio de sesión (éxito, fallo, bloqueo) se registrarán en la base de datos para revisión en la pestaña Historial.',
			'IP Whitelist' => 'Lista blanca de IP',
			'Whitelisted IP Addresses' => 'Direcciones IP en lista blanca',
			'Enter IP addresses that should bypass brute force protection. Enter one IP per line or separate with commas. Examples:' => 'Ingresa las direcciones IP que deben omitir la protección contra fuerza bruta. Ingresa una IP por línea o sepáralas con comas. Ejemplos:',
			'Progressive Lockout' => 'Bloqueo progresivo',
			'Progressive Lockout Delays' => 'Retrasos de bloqueo progresivo',
			'Enable progressive lockout delays' => 'Activar retrasos de bloqueo progresivo',
			'Each subsequent lockout doubles the wait time. Helps stop persistent attackers. Resets after 24 hours of no attempts.' => 'Cada bloqueo subsiguiente duplica el tiempo de espera. Ayuda a detener atacantes persistentes. Se reinicia después de 24 horas sin intentos.',
			'Maximum Lockout Duration' => 'Duración máxima de bloqueo',
			'Maximum lockout duration even with progressive delays (60-1440 minutes).' => 'Duración máxima de bloqueo incluso con retrasos progresivos (60-1440 minutos).',
			'Enable Email Notifications' => 'Activar notificaciones por correo',
			'Enable email notification features' => 'Activar funciones de notificación por correo',
			'Master toggle for all email notifications. When enabled, you can configure individual notification types below.' => 'Control principal para todas las notificaciones por correo. Cuando está activado, puedes configurar los tipos individuales abajo.',
			'Configure email notifications for login lockouts and alerts. These settings are independent of the Advanced Security features and can be enabled separately.' => 'Configura notificaciones por correo para bloqueos de inicio de sesión y alertas. Estas configuraciones son independientes de las funciones de Seguridad Avanzada.',
			'Lockout Notifications' => 'Notificaciones de bloqueo',
			'Lockout Email Notification' => 'Notificación por correo de bloqueo',
			'Send email when a lockout occurs' => 'Enviar correo cuando ocurra un bloqueo',
			'Notification Email' => 'Correo de notificación',
			'Email address to receive lockout notifications. Defaults to site admin email.' => 'Dirección de correo para recibir notificaciones de bloqueo. Por defecto, correo del administrador.',
			'Email Subject' => 'Asunto del correo',
			'Custom email subject. Leave blank for default.' => 'Asunto personalizado. Dejar en blanco para usar el predeterminado.',
			'Email Body' => 'Cuerpo del correo',
			'Custom email body. Leave blank for default. Available placeholders:' => 'Cuerpo del correo personalizado. Dejar en blanco para usar el predeterminado. Marcadores disponibles:',
			'Number of failed attempts' => 'Número de intentos fallidos',
			'Current time' => 'Hora actual',
			'Site name' => 'Nombre del sitio',
			'Site URL' => 'URL del sitio',
			'Login Alerts' => 'Alertas de inicio de sesión',
			'Admin Login Alert' => 'Alerta de inicio de sesión del administrador',
			'Send email to admin when any user logs in' => 'Enviar correo al administrador cuando cualquier usuario inicie sesión',
			'New IP Login Alert' => 'Alerta de inicio de sesión desde nueva IP',
			'Email user when they login from a new IP address' => 'Enviar correo al usuario cuando inicie sesión desde una nueva dirección IP',
			'Users will be notified when their account is accessed from an IP address they have not used before.' => 'Los usuarios serán notificados cuando se acceda a su cuenta desde una dirección IP que no han usado antes.',
			'Login Attempt History' => 'Historial de intentos de inicio de sesión',
			'Filter by IP' => 'Filtrar por IP',
			'Filter by Username' => 'Filtrar por nombre de usuario',
			'Filter by Result' => 'Filtrar por resultado',
			'All Results' => 'Todos los resultados',
			'Success' => 'Éxito',
			'Failed' => 'Fallido',
			'Locked Out' => 'Bloqueado',
			'Whitelisted' => 'En lista blanca',
			'Date From' => 'Fecha desde',
			'Date To' => 'Fecha hasta',
			'Filter' => 'Filtrar',
			'Clear Filters' => 'Limpiar filtros',
			'No login attempts recorded yet.' => 'No se han registrado intentos de inicio de sesión aún.',
			'Showing %1$d to %2$d of %3$d entries' => 'Mostrando %1$d a %2$d de %3$d entradas',
			'%d items' => '%d elementos',
			'&laquo; Previous' => '&laquo; Anterior',
			'Next &raquo;' => 'Siguiente &raquo;',
			'Clear All Logs' => 'Borrar todos los registros',
			'Are you sure you want to clear all login logs?' => '¿Estás seguro de que deseas borrar todos los registros de inicio de sesión?',
			'Export to CSV' => 'Exportar a CSV',
			'All login logs have been cleared.' => 'Todos los registros de inicio de sesión han sido borrados.',
			'How to Use MSC Stealth Login' => 'Cómo usar MSC Stealth Login',
			'MSC Stealth Login helps secure your WordPress site by hiding the default login page and adding brute force protection.' => 'MSC Stealth Login ayuda a proteger tu sitio WordPress ocultando la página de inicio de sesión predeterminada y añadiendo protección contra fuerza bruta.',
			'Setting Up Your Custom Login URL' => 'Configuración de tu URL de inicio de sesión personalizada',
			'Go to the Settings tab above.' => 'Ve a la pestaña de Configuración arriba.',
			'Enter a custom login slug (e.g., "my-secret-login" or "admin-access").' => 'Ingresa un slug de inicio de sesión personalizado (ej. "my-secret-login" o "admin-access").',
			'Click Save Settings.' => 'Haz clic en Guardar configuración.',
			'Your new login URL will be:' => 'Tu nueva URL de inicio de sesión será:',
			'Bookmark this URL immediately!' => '¡Guarda esta URL en marcadores inmediatamente!',
			'Important:' => 'Importante:',
			'You will no longer be able to access wp-login.php directly.' => 'Ya no podrás acceder directamente a wp-login.php.',
			'Security Features Explained' => 'Funciones de seguridad explicadas',
			'Frequently Asked Questions' => 'Preguntas frecuentes',
			'What happens if I forget my custom login URL?' => '¿Qué pasa si olvido mi URL de inicio de sesión personalizada?',
			'Will this break my site?' => '¿Esto romperá mi sitio?',
			'Can I still use wp-admin after logging in?' => '¿Puedo seguir usando wp-admin después de iniciar sesión?',
			'Will this work with my security plugin?' => '¿Funcionará esto con mi plugin de seguridad?',
			'Does this work with WooCommerce?' => '¿Funciona esto con WooCommerce?',
			'How do I disable the plugin if I get locked out?' => '¿Cómo desactivo el plugin si me bloquean?',
			'Need Help?' => '¿Necesitas ayuda?',
			"If you have questions, encounter bugs, or need setup assistance, we're here to help." => 'Si tienes preguntas, encuentras errores o necesitas ayuda con la configuración, estamos aquí para ayudarte.',
			'Get Support' => 'Obtener soporte',
		),
	),

	// -----------------------------------------------------------------
	// Spanish (Mexico) — inherit from es_ES
	// -----------------------------------------------------------------
	'es_MX' => array(
		'language'     => 'es_MX',
		'language_name' => 'Spanish (Mexico)',
		'plural_forms' => 'nplurals=2; plural=(n != 1);',
		'strings'      => 'es_ES',
	),

	// -----------------------------------------------------------------
	// French (France)
	// -----------------------------------------------------------------
	'fr_FR' => array(
		'language'     => 'fr_FR',
		'language_name' => 'French (France)',
		'plural_forms' => 'nplurals=2; plural=(n > 1);',
		'strings'      => array(
			'MSC Stealth Login' => 'MSC Stealth Login',
			'Hide your login page, block brute force attacks, and protect your WordPress site from unauthorized access. Complete free plugin with all features included.' => 'Masquez votre page de connexion, bloquez les attaques par force brute et protégez votre site WordPress contre les accès non autorisés. Extension gratuite complète avec toutes les fonctionnalités.',
			'Anomalous Developers' => 'Anomalous Developers',
			'The following plugins were detected: %s. You may need to configure cache or security exclusions for your custom login URL.' => "Les extensions suivantes ont été détectées : %s. Vous devrez peut-être configurer des exclusions de cache ou de sécurité pour votre URL de connexion personnalisée.",
			'You do not have permission to access this page.' => "Vous n'avez pas la permission d'accéder à cette page.",
			'Pingback functionality is disabled.' => 'La fonctionnalité de rétrolien est désactivée.',
			'Too many failed login attempts. Please try again in %d minutes.' => 'Trop de tentatives de connexion échouées. Veuillez réessayer dans %d minutes.',
			'Too many failed login attempts. You are temporarily blocked for %1$d days and %2$d hours.' => 'Trop de tentatives de connexion échouées. Vous êtes temporairement bloqué pour %1$d jours et %2$d heures.',
			'Too many failed login attempts. You are temporarily blocked for %1$d hours and %2$d minutes.' => 'Trop de tentatives de connexion échouées. Vous êtes temporairement bloqué pour %1$d heures et %2$d minutes.',
			'[%s] Brute Force Lockout Alert' => '[%s] Alerte de verrouillage par force brute',
			'Locked Out' => 'Verrouillé',
			'Access Denied' => 'Accès refusé',
			'The login page you are trying to access has been hidden for security reasons. Please use the correct login URL to access this site.' => "La page de connexion à laquelle vous essayez d'accéder a été masquée pour des raisons de sécurité. Veuillez utiliser l'URL de connexion correcte.",
			'[%s] Your Login URL Recovery' => '[%s] Récupération de votre URL de connexion',
			'You requested a login URL recovery for %1$s. Your stealth login URL is: %2$s' => 'Vous avez demandé une récupération d\'URL de connexion pour %1$s. Votre URL de connexion furtive est : %2$s',
			'Recovery email sent successfully.' => 'E-mail de récupération envoyé avec succès.',
			'Failed to send recovery email. Please check the email address.' => "Échec de l'envoi de l'e-mail de récupération. Veuillez vérifier l'adresse e-mail.",
			'Failed to send recovery email.' => "Échec de l'envoi de l'e-mail de récupération.",
			'[%s] User Login Alert' => '[%s] Alerte de connexion utilisateur',
			'[%s] New Login Location Detected' => '[%s] Nouvel emplacement de connexion détecté',
			'IP Address' => 'Adresse IP',
			'Username' => "Nom d'utilisateur",
			'Result' => 'Résultat',
			'User Agent' => 'Agent utilisateur',
			'Date/Time' => 'Date/Heure',
			'Settings updated successfully.' => 'Paramètres mis à jour avec succès.',
			'Settings' => 'Paramètres',
			'Advanced' => 'Avancé',
			'Email' => 'E-mail',
			'History' => 'Historique',
			'Support' => 'Support',
			'Enable Stealth Login' => 'Activer la connexion furtive',
			'Enable stealth login protection' => 'Activer la protection de connexion furtive',
			'Custom Login URL' => 'URL de connexion personnalisée',
			'Your login page will be at: %s' => 'Votre page de connexion sera à : %s',
			'Hide wp-admin' => 'Masquer wp-admin',
			'Block direct access to wp-admin directory' => "Bloquer l'accès direct au répertoire wp-admin",
			'Users will be redirected to your custom login URL or the specified URL when trying to access wp-admin directly.' => "Les utilisateurs seront redirigés vers votre URL de connexion personnalisée ou l'URL spécifiée lorsqu'ils tenteront d'accéder directement à wp-admin.",
			'wp-admin Redirect URL' => 'URL de redirection wp-admin',
			'Where to redirect users when they try to access wp-admin directly. Defaults to homepage.' => "Où rediriger les utilisateurs lorsqu'ils tentent d'accéder directement à wp-admin. Par défaut, la page d'accueil.",
			'Logout Redirect URL' => 'URL de redirection à la déconnexion',
			'Where to redirect users after logging out. Defaults to homepage.' => "Où rediriger les utilisateurs après la déconnexion. Par défaut, la page d'accueil.",
			'Login URL Preview' => "Aperçu de l'URL de connexion",
			'This is what your custom login URL will look like.' => 'Voici à quoi ressemblera votre URL de connexion personnalisée.',
			'Emergency Recovery URL' => "URL de récupération d'urgence",
			'Current Recovery URL:' => 'URL de récupération actuelle :',
			'Copy URL' => "Copier l'URL",
			'Copied!' => 'Copié !',
			'Bookmark this URL! If you lose access to your custom login URL, use this recovery URL to access wp-login.php and then navigate to settings to find your custom URL.' => "Ajoutez cette URL à vos favoris ! Si vous perdez l'accès à votre URL personnalisée, utilisez cette URL de récupération pour accéder à wp-login.php.",
			'Regenerate Recovery URL' => "Régénérer l'URL de récupération",
			'Are you sure? This will invalidate the current recovery URL.' => 'Êtes-vous sûr ? Cela invalidera l\'URL de récupération actuelle.',
			'Recovery URL regenerated successfully!' => 'URL de récupération régénérée avec succès !',
			'Save Settings' => 'Enregistrer les paramètres',
			'Enable Advanced Security Features' => 'Activer les fonctionnalités de sécurité avancées',
			'XML-RPC & REST API Protection' => 'Protection XML-RPC et API REST',
			'Disable XML-RPC' => 'Désactiver XML-RPC',
			'Block XML-RPC requests' => 'Bloquer les requêtes XML-RPC',
			'Disable REST API User Enumeration' => "Désactiver l'énumération des utilisateurs de l'API REST",
			'Block REST API user enumeration' => "Bloquer l'énumération des utilisateurs de l'API REST",
			'Brute Force Protection' => 'Protection contre la force brute',
			'Enable Brute Force Protection' => 'Activer la protection contre la force brute',
			'Enable login attempt limiting' => 'Activer la limitation des tentatives de connexion',
			'Max Login Attempts' => 'Tentatives de connexion maximum',
			'Number of failed attempts before a temporary lockout (1-10).' => "Nombre de tentatives échouées avant un verrouillage temporaire (1-10).",
			'Lockout Duration' => 'Durée du verrouillage',
			'minutes' => 'minutes',
			'How long the lockout lasts in minutes (5-60).' => 'Durée du verrouillage en minutes (5-60).',
			'Login Logging' => 'Journalisation des connexions',
			'Enable Login Logging' => 'Activer la journalisation des connexions',
			'Log all login attempts to database' => 'Enregistrer toutes les tentatives de connexion dans la base de données',
			'IP Whitelist' => 'Liste blanche IP',
			'Whitelisted IP Addresses' => 'Adresses IP en liste blanche',
			'Progressive Lockout' => 'Verrouillage progressif',
			'Progressive Lockout Delays' => 'Délais de verrouillage progressif',
			'Enable progressive lockout delays' => 'Activer les délais de verrouillage progressif',
			'Maximum Lockout Duration' => 'Durée maximale de verrouillage',
			'Enable Email Notifications' => 'Activer les notifications par e-mail',
			'Enable email notification features' => "Activer les fonctionnalités de notification par e-mail",
			'Lockout Notifications' => 'Notifications de verrouillage',
			'Lockout Email Notification' => 'Notification par e-mail de verrouillage',
			'Send email when a lockout occurs' => "Envoyer un e-mail lorsqu'un verrouillage se produit",
			'Notification Email' => 'E-mail de notification',
			'Email Subject' => "Objet de l'e-mail",
			'Email Body' => "Corps de l'e-mail",
			'Number of failed attempts' => 'Nombre de tentatives échouées',
			'Current time' => 'Heure actuelle',
			'Site name' => 'Nom du site',
			'Site URL' => 'URL du site',
			'Login Alerts' => 'Alertes de connexion',
			'Admin Login Alert' => "Alerte de connexion administrateur",
			'Send email to admin when any user logs in' => "Envoyer un e-mail à l'administrateur lorsqu'un utilisateur se connecte",
			'New IP Login Alert' => 'Alerte de connexion depuis une nouvelle IP',
			'Email user when they login from a new IP address' => "Envoyer un e-mail à l'utilisateur lorsqu'il se connecte depuis une nouvelle adresse IP",
			'Login Attempt History' => 'Historique des tentatives de connexion',
			'Filter by IP' => 'Filtrer par IP',
			'Filter by Username' => "Filtrer par nom d'utilisateur",
			'Filter by Result' => 'Filtrer par résultat',
			'All Results' => 'Tous les résultats',
			'Success' => 'Réussite',
			'Failed' => 'Échoué',
			'Locked Out' => 'Verrouillé',
			'Whitelisted' => 'En liste blanche',
			'Date From' => 'Date de début',
			'Date To' => 'Date de fin',
			'Filter' => 'Filtrer',
			'Clear Filters' => 'Effacer les filtres',
			'No login attempts recorded yet.' => 'Aucune tentative de connexion enregistrée.',
			'Showing %1$d to %2$d of %3$d entries' => 'Affichage de %1$d à %2$d sur %3$d entrées',
			'%d items' => '%d éléments',
			'&laquo; Previous' => '&laquo; Précédent',
			'Next &raquo;' => 'Suivant &raquo;',
			'Clear All Logs' => 'Effacer tous les journaux',
			'Are you sure you want to clear all login logs?' => 'Êtes-vous sûr de vouloir effacer tous les journaux de connexion ?',
			'Export to CSV' => 'Exporter en CSV',
			'All login logs have been cleared.' => 'Tous les journaux de connexion ont été effacés.',
			'How to Use MSC Stealth Login' => 'Comment utiliser MSC Stealth Login',
			'Setting Up Your Custom Login URL' => 'Configuration de votre URL de connexion personnalisée',
			'Go to the Settings tab above.' => "Allez dans l'onglet Paramètres ci-dessus.",
			'Click Save Settings.' => 'Cliquez sur Enregistrer les paramètres.',
			'Your new login URL will be:' => 'Votre nouvelle URL de connexion sera :',
			'Bookmark this URL immediately!' => 'Ajoutez cette URL à vos favoris immédiatement !',
			'Important:' => 'Important :',
			'You will no longer be able to access wp-login.php directly.' => 'Vous ne pourrez plus accéder directement à wp-login.php.',
			'Security Features Explained' => 'Fonctionnalités de sécurité expliquées',
			'Frequently Asked Questions' => 'Questions fréquemment posées',
			'Need Help?' => "Besoin d'aide ?",
			"If you have questions, encounter bugs, or need setup assistance, we're here to help." => "Si vous avez des questions, rencontrez des bugs ou avez besoin d'aide pour la configuration, nous sommes là pour vous aider.",
			'Get Support' => 'Obtenir du support',
		),
	),

	// -----------------------------------------------------------------
	// French (Canada) — inherit from fr_FR
	// -----------------------------------------------------------------
	'fr_CA' => array(
		'language'     => 'fr_CA',
		'language_name' => 'French (Canada)',
		'plural_forms' => 'nplurals=2; plural=(n > 1);',
		'strings'      => 'fr_FR',
	),

	// -----------------------------------------------------------------
	// Italian (Italy)
	// -----------------------------------------------------------------
	'it_IT' => array(
		'language'     => 'it_IT',
		'language_name' => 'Italian (Italy)',
		'plural_forms' => 'nplurals=2; plural=(n != 1);',
		'strings'      => array(
			'MSC Stealth Login' => 'MSC Stealth Login',
			'Hide your login page, block brute force attacks, and protect your WordPress site from unauthorized access. Complete free plugin with all features included.' => 'Nascondi la tua pagina di accesso, blocca gli attacchi di forza bruta e proteggi il tuo sito WordPress da accessi non autorizzati. Plugin gratuito completo con tutte le funzionalità.',
			'You do not have permission to access this page.' => 'Non hai il permesso di accedere a questa pagina.',
			'Too many failed login attempts. Please try again in %d minutes.' => 'Troppi tentativi di accesso falliti. Riprova tra %d minuti.',
			'Too many failed login attempts. You are temporarily blocked for %1$d days and %2$d hours.' => 'Troppi tentativi di accesso falliti. Sei temporaneamente bloccato per %1$d giorni e %2$d ore.',
			'Too many failed login attempts. You are temporarily blocked for %1$d hours and %2$d minutes.' => 'Troppi tentativi di accesso falliti. Sei temporaneamente bloccato per %1$d ore e %2$d minuti.',
			'Locked Out' => 'Bloccato',
			'Access Denied' => 'Accesso negato',
			'The login page you are trying to access has been hidden for security reasons. Please use the correct login URL to access this site.' => "La pagina di accesso a cui stai cercando di accedere è stata nascosta per motivi di sicurezza. Utilizza l'URL di accesso corretto.",
			'IP Address' => 'Indirizzo IP',
			'Username' => 'Nome utente',
			'Result' => 'Risultato',
			'User Agent' => 'User agent',
			'Date/Time' => 'Data/Ora',
			'Settings updated successfully.' => 'Impostazioni aggiornate con successo.',
			'Settings' => 'Impostazioni',
			'Advanced' => 'Avanzate',
			'Email' => 'E-mail',
			'History' => 'Cronologia',
			'Support' => 'Supporto',
			'Enable Stealth Login' => 'Abilita accesso nascosto',
			'Custom Login URL' => 'URL di accesso personalizzato',
			'Your login page will be at: %s' => 'La tua pagina di accesso sarà su: %s',
			'Hide wp-admin' => 'Nascondi wp-admin',
			'Block direct access to wp-admin directory' => "Blocca l'accesso diretto alla cartella wp-admin",
			'Logout Redirect URL' => 'URL di reindirizzamento alla disconnessione',
			'Login URL Preview' => "Anteprima dell'URL di accesso",
			'Emergency Recovery URL' => "URL di recupero d'emergenza",
			'Current Recovery URL:' => 'URL di recupero attuale:',
			'Copy URL' => "Copia l'URL",
			'Copied!' => 'Copiato!',
			'Regenerate Recovery URL' => 'Rigenera URL di recupero',
			'Save Settings' => 'Salva impostazioni',
			'XML-RPC & REST API Protection' => 'Protezione XML-RPC e API REST',
			'Disable XML-RPC' => 'Disabilita XML-RPC',
			'Brute Force Protection' => 'Protezione forza bruta',
			'Enable Brute Force Protection' => 'Abilita protezione forza bruta',
			'Max Login Attempts' => 'Tentativi di accesso massimi',
			'Lockout Duration' => 'Durata del blocco',
			'minutes' => 'minuti',
			'Login Logging' => 'Registrazione accessi',
			'Enable Login Logging' => 'Abilita registrazione accessi',
			'IP Whitelist' => 'Lista bianca IP',
			'Progressive Lockout' => 'Blocco progressivo',
			'Maximum Lockout Duration' => 'Durata massima del blocco',
			'Enable Email Notifications' => 'Abilita notifiche e-mail',
			'Lockout Notifications' => 'Notifiche di blocco',
			'Login Alerts' => 'Avvisi di accesso',
			'Admin Login Alert' => "Avviso accesso dell'amministratore",
			'New IP Login Alert' => 'Avviso accesso da nuovo IP',
			'Login Attempt History' => 'Cronologia dei tentativi di accesso',
			'Filter by IP' => 'Filtra per IP',
			'Filter by Username' => 'Filtra per nome utente',
			'Filter by Result' => 'Filtra per risultato',
			'All Results' => 'Tutti i risultati',
			'Success' => 'Riuscito',
			'Failed' => 'Fallito',
			'Locked Out' => 'Bloccato',
			'Whitelisted' => 'In lista bianca',
			'Date From' => 'Data da',
			'Date To' => 'Data a',
			'Filter' => 'Filtra',
			'Clear Filters' => 'Cancella filtri',
			'No login attempts recorded yet.' => 'Nessun tentativo di accesso registrato.',
			'Showing %1$d to %2$d of %3$d entries' => 'Visualizzazione da %1$d a %2$d di %3$d voci',
			'%d items' => '%d elementi',
			'&laquo; Previous' => '&laquo; Precedente',
			'Next &raquo;' => 'Successivo &raquo;',
			'Clear All Logs' => 'Cancella tutti i registri',
			'Export to CSV' => 'Esporta in CSV',
			'All login logs have been cleared.' => 'Tutti i registri di accesso sono stati cancellati.',
			'Frequently Asked Questions' => 'Domande frequenti',
			'Need Help?' => 'Hai bisogno di aiuto?',
			'Get Support' => 'Ottieni supporto',
		),
	),

	// -----------------------------------------------------------------
	// Japanese
	// -----------------------------------------------------------------
	'ja' => array(
		'language'     => 'ja',
		'language_name' => 'Japanese',
		'plural_forms' => 'nplurals=1; plural=0;',
		'strings'      => array(
			'MSC Stealth Login' => 'MSC ステルスログイン',
			'Hide your login page, block brute force attacks, and protect your WordPress site from unauthorized access. Complete free plugin with all features included.' => 'ログインページを隠し、ブルートフォース攻撃をブロックし、不正アクセスからWordPressサイトを保護します。全機能搭載の完全無料プラグイン。',
			'You do not have permission to access this page.' => 'このページにアクセスする権限がありません。',
			'Pingback functionality is disabled.' => 'ピンバック機能は無効です。',
			'Too many failed login attempts. Please try again in %d minutes.' => 'ログイン試行回数が多すぎます。%d分後に再試行してください。',
			'Too many failed login attempts. You are temporarily blocked for %1$d days and %2$d hours.' => 'ログイン試行回数が多すぎます。%1$d日と%2$d時間一時的にブロックされています。',
			'Too many failed login attempts. You are temporarily blocked for %1$d hours and %2$d minutes.' => 'ログイン試行回数が多すぎます。%1$d時間と%2$d分一時的にブロックされています。',
			'[%s] Brute Force Lockout Alert' => '[%s] ブルートフォースロックアウト警告',
			'Locked Out' => 'ロックアウト',
			'Access Denied' => 'アクセス拒否',
			'The login page you are trying to access has been hidden for security reasons. Please use the correct login URL to access this site.' => 'アクセスしようとしているログインページはセキュリティ上の理由で非表示になっています。正しいログインURLを使用してください。',
			'[%s] Your Login URL Recovery' => '[%s] ログインURL回復',
			'Recovery email sent successfully.' => '回復メールが正常に送信されました。',
			'Failed to send recovery email.' => '回復メールの送信に失敗しました。',
			'[%s] User Login Alert' => '[%s] ユーザーログイン警告',
			'[%s] New Login Location Detected' => '[%s] 新しいログイン場所が検出されました',
			'IP Address' => 'IPアドレス',
			'Username' => 'ユーザー名',
			'Result' => '結果',
			'User Agent' => 'ユーザーエージェント',
			'Date/Time' => '日時',
			'Settings updated successfully.' => '設定が正常に更新されました。',
			'Settings' => '設定',
			'Advanced' => '詳細',
			'Email' => 'メール',
			'History' => '履歴',
			'Support' => 'サポート',
			'Enable Stealth Login' => 'ステルスログインを有効にする',
			'Enable stealth login protection' => 'ステルスログイン保護を有効にする',
			'Custom Login URL' => 'カスタムログインURL',
			'Your login page will be at: %s' => 'ログインページのURL: %s',
			'Hide wp-admin' => 'wp-adminを隠す',
			'Block direct access to wp-admin directory' => 'wp-adminディレクトリへの直接アクセスをブロック',
			'wp-admin Redirect URL' => 'wp-adminリダイレクトURL',
			'Logout Redirect URL' => 'ログアウトリダイレクトURL',
			'Login URL Preview' => 'ログインURLプレビュー',
			'This is what your custom login URL will look like.' => 'カスタムログインURLの表示例です。',
			'Emergency Recovery URL' => '緊急回復URL',
			'Current Recovery URL:' => '現在の回復URL:',
			'Copy URL' => 'URLをコピー',
			'Copied!' => 'コピーしました！',
			'Regenerate Recovery URL' => '回復URLを再生成',
			'Are you sure? This will invalidate the current recovery URL.' => '本当によろしいですか？現在の回復URLが無効になります。',
			'Recovery URL regenerated successfully!' => '回復URLが正常に再生成されました！',
			'Save Settings' => '設定を保存',
			'Enable Advanced Security Features' => '高度なセキュリティ機能を有効にする',
			'XML-RPC & REST API Protection' => 'XML-RPC & REST API保護',
			'Disable XML-RPC' => 'XML-RPCを無効にする',
			'Block XML-RPC requests' => 'XML-RPCリクエストをブロック',
			'Disable REST API User Enumeration' => 'REST APIユーザー列挙を無効にする',
			'Block REST API user enumeration' => 'REST APIユーザー列挙をブロック',
			'Brute Force Protection' => 'ブルートフォース保護',
			'Enable Brute Force Protection' => 'ブルートフォース保護を有効にする',
			'Enable login attempt limiting' => 'ログイン試行制限を有効にする',
			'Max Login Attempts' => '最大ログイン試行回数',
			'Lockout Duration' => 'ロックアウト期間',
			'minutes' => '分',
			'Login Logging' => 'ログイン記録',
			'Enable Login Logging' => 'ログイン記録を有効にする',
			'Log all login attempts to database' => 'すべてのログイン試行をデータベースに記録',
			'IP Whitelist' => 'IPホワイトリスト',
			'Whitelisted IP Addresses' => 'ホワイトリストのIPアドレス',
			'Progressive Lockout' => 'プログレッシブロックアウト',
			'Progressive Lockout Delays' => 'プログレッシブロックアウト遅延',
			'Enable progressive lockout delays' => 'プログレッシブロックアウト遅延を有効にする',
			'Maximum Lockout Duration' => '最大ロックアウト期間',
			'Enable Email Notifications' => 'メール通知を有効にする',
			'Enable email notification features' => 'メール通知機能を有効にする',
			'Lockout Notifications' => 'ロックアウト通知',
			'Lockout Email Notification' => 'ロックアウトメール通知',
			'Send email when a lockout occurs' => 'ロックアウト発生時にメールを送信',
			'Notification Email' => '通知メール',
			'Email Subject' => 'メール件名',
			'Email Body' => 'メール本文',
			'Number of failed attempts' => '失敗した試行回数',
			'Current time' => '現在の時刻',
			'Site name' => 'サイト名',
			'Site URL' => 'サイトURL',
			'Login Alerts' => 'ログイン警告',
			'Admin Login Alert' => '管理者ログイン警告',
			'Send email to admin when any user logs in' => 'ユーザーがログインしたら管理者にメールを送信',
			'New IP Login Alert' => '新しいIPログイン警告',
			'Email user when they login from a new IP address' => '新しいIPアドレスからログインしたらユーザーにメールを送信',
			'Login Attempt History' => 'ログイン試行履歴',
			'Filter by IP' => 'IPでフィルタ',
			'Filter by Username' => 'ユーザー名でフィルタ',
			'Filter by Result' => '結果でフィルタ',
			'All Results' => 'すべての結果',
			'Success' => '成功',
			'Failed' => '失敗',
			'Locked Out' => 'ロックアウト',
			'Whitelisted' => 'ホワイトリスト',
			'Date From' => '開始日',
			'Date To' => '終了日',
			'Filter' => 'フィルタ',
			'Clear Filters' => 'フィルタをクリア',
			'No login attempts recorded yet.' => 'ログイン試行はまだ記録されていません。',
			'Showing %1$d to %2$d of %3$d entries' => '%3$d件中%1$dから%2$dを表示',
			'%d items' => '%d件',
			'&laquo; Previous' => '&laquo; 前へ',
			'Next &raquo;' => '次へ &raquo;',
			'Clear All Logs' => 'すべてのログを消去',
			'Are you sure you want to clear all login logs?' => 'すべてのログイン記録を消去してもよろしいですか？',
			'Export to CSV' => 'CSVにエクスポート',
			'All login logs have been cleared.' => 'すべてのログイン記録が消去されました。',
			'Frequently Asked Questions' => 'よくある質問',
			'Need Help?' => 'ヘルプが必要ですか？',
			'Get Support' => 'サポートを受ける',
		),
	),

	// -----------------------------------------------------------------
	// Dutch (Netherlands)
	// -----------------------------------------------------------------
	'nl_NL' => array(
		'language'     => 'nl_NL',
		'language_name' => 'Dutch (Netherlands)',
		'plural_forms' => 'nplurals=2; plural=(n != 1);',
		'strings'      => array(
			'MSC Stealth Login' => 'MSC Stealth Login',
			'Hide your login page, block brute force attacks, and protect your WordPress site from unauthorized access. Complete free plugin with all features included.' => 'Verberg je inlogpagina, blokkeer brute force-aanvallen en bescherm je WordPress-site tegen ongeautoriseerde toegang. Volledige gratis plugin met alle functies.',
			'You do not have permission to access this page.' => 'Je hebt geen toestemming om deze pagina te openen.',
			'Too many failed login attempts. Please try again in %d minutes.' => 'Te veel mislukte inlogpogingen. Probeer het opnieuw over %d minuten.',
			'Too many failed login attempts. You are temporarily blocked for %1$d days and %2$d hours.' => 'Te veel mislukte inlogpogingen. Je bent tijdelijk geblokkeerd voor %1$d dagen en %2$d uur.',
			'Too many failed login attempts. You are temporarily blocked for %1$d hours and %2$d minutes.' => 'Te veel mislukte inlogpogingen. Je bent tijdelijk geblokkeerd voor %1$d uur en %2$d minuten.',
			'Locked Out' => 'Geblokkeerd',
			'Access Denied' => 'Toegang geweigerd',
			'The login page you are trying to access has been hidden for security reasons. Please use the correct login URL to access this site.' => 'De inlogpagina die je probeert te openen is verborgen om veiligheidsredenen. Gebruik de juiste inlog-URL.',
			'IP Address' => 'IP-adres',
			'Username' => 'Gebruikersnaam',
			'Result' => 'Resultaat',
			'User Agent' => 'User agent',
			'Date/Time' => 'Datum/Tijd',
			'Settings updated successfully.' => 'Instellingen succesvol bijgewerkt.',
			'Settings' => 'Instellingen',
			'Advanced' => 'Geavanceerd',
			'Email' => 'E-mail',
			'History' => 'Geschiedenis',
			'Support' => 'Ondersteuning',
			'Enable Stealth Login' => 'Stealth Login inschakelen',
			'Custom Login URL' => 'Aangepaste inlog-URL',
			'Your login page will be at: %s' => 'Je inlogpagina is bereikbaar op: %s',
			'Hide wp-admin' => 'wp-admin verbergen',
			'Block direct access to wp-admin directory' => 'Directe toegang tot wp-admin-map blokkeren',
			'Logout Redirect URL' => 'Uitlog-omleidings-URL',
			'Login URL Preview' => 'Inlog-URL voorbeeld',
			'Emergency Recovery URL' => 'Noodherstel-URL',
			'Current Recovery URL:' => 'Huidige herstel-URL:',
			'Copy URL' => 'URL kopiëren',
			'Copied!' => 'Gekopieerd!',
			'Regenerate Recovery URL' => 'Herstel-URL opnieuw genereren',
			'Save Settings' => 'Instellingen opslaan',
			'XML-RPC & REST API Protection' => 'XML-RPC & REST API-bescherming',
			'Disable XML-RPC' => 'XML-RPC uitschakelen',
			'Brute Force Protection' => 'Brute force-bescherming',
			'Enable Brute Force Protection' => 'Brute force-bescherming inschakelen',
			'Max Login Attempts' => 'Maximale inlogpogingen',
			'Lockout Duration' => 'Blokkeringsduur',
			'minutes' => 'minuten',
			'Login Logging' => 'Inlogregistratie',
			'Enable Login Logging' => 'Inlogregistratie inschakelen',
			'IP Whitelist' => 'IP-whitelist',
			'Progressive Lockout' => 'Progressieve blokkering',
			'Maximum Lockout Duration' => 'Maximale blokkeringsduur',
			'Enable Email Notifications' => 'E-mailmeldingen inschakelen',
			'Lockout Notifications' => 'Blokkeringsmeldingen',
			'Login Alerts' => 'Inlogwaarschuwingen',
			'Admin Login Alert' => 'Admin-inlogwaarschuwing',
			'New IP Login Alert' => 'Nieuwe IP-inlogwaarschuwing',
			'Login Attempt History' => 'Inlogpogingengeschiedenis',
			'Filter by IP' => 'Filteren op IP',
			'Filter by Username' => 'Filteren op gebruikersnaam',
			'Filter by Result' => 'Filteren op resultaat',
			'All Results' => 'Alle resultaten',
			'Success' => 'Geslaagd',
			'Failed' => 'Mislukt',
			'Locked Out' => 'Geblokkeerd',
			'Whitelisted' => 'Op whitelist',
			'Date From' => 'Datum van',
			'Date To' => 'Datum tot',
			'Filter' => 'Filteren',
			'Clear Filters' => 'Filters wissen',
			'No login attempts recorded yet.' => 'Nog geen inlogpogingen geregistreerd.',
			'Showing %1$d to %2$d of %3$d entries' => 'Weergave %1$d tot %2$d van %3$d vermeldingen',
			'%d items' => '%d items',
			'&laquo; Previous' => '&laquo; Vorige',
			'Next &raquo;' => 'Volgende &raquo;',
			'Clear All Logs' => 'Alle logboeken wissen',
			'Export to CSV' => 'Exporteren naar CSV',
			'All login logs have been cleared.' => 'Alle inloglogboeken zijn gewist.',
			'Frequently Asked Questions' => 'Veelgestelde vragen',
			'Need Help?' => 'Hulp nodig?',
			'Get Support' => 'Ondersteuning krijgen',
		),
	),

	// -----------------------------------------------------------------
	// Dutch (Belgium) — inherit from nl_NL
	// -----------------------------------------------------------------
	'nl_BE' => array(
		'language'     => 'nl_BE',
		'language_name' => 'Dutch (Belgium)',
		'plural_forms' => 'nplurals=2; plural=(n != 1);',
		'strings'      => 'nl_NL',
	),

	// -----------------------------------------------------------------
	// Brazilian Portuguese
	// -----------------------------------------------------------------
	'pt_BR' => array(
		'language'     => 'pt_BR',
		'language_name' => 'Portuguese (Brazil)',
		'plural_forms' => 'nplurals=2; plural=(n > 1);',
		'strings'      => array(
			'MSC Stealth Login' => 'MSC Stealth Login',
			'Hide your login page, block brute force attacks, and protect your WordPress site from unauthorized access. Complete free plugin with all features included.' => 'Oculte sua página de login, bloqueie ataques de força bruta e proteja seu site WordPress contra acessos não autorizados. Plugin gratuito completo com todos os recursos.',
			'You do not have permission to access this page.' => 'Você não tem permissão para acessar esta página.',
			'Too many failed login attempts. Please try again in %d minutes.' => 'Muitas tentativas de login falhas. Tente novamente em %d minutos.',
			'Too many failed login attempts. You are temporarily blocked for %1$d days and %2$d hours.' => 'Muitas tentativas de login falhas. Você está temporariamente bloqueado por %1$d dias e %2$d horas.',
			'Too many failed login attempts. You are temporarily blocked for %1$d hours and %2$d minutes.' => 'Muitas tentativas de login falhas. Você está temporariamente bloqueado por %1$d horas e %2$d minutos.',
			'Locked Out' => 'Bloqueado',
			'Access Denied' => 'Acesso negado',
			'The login page you are trying to access has been hidden for security reasons. Please use the correct login URL to access this site.' => 'A página de login que você está tentando acessar foi ocultada por motivos de segurança. Use a URL de login correta.',
			'IP Address' => 'Endereço IP',
			'Username' => 'Nome de usuário',
			'Result' => 'Resultado',
			'User Agent' => 'User agent',
			'Date/Time' => 'Data/Hora',
			'Settings updated successfully.' => 'Configurações atualizadas com sucesso.',
			'Settings' => 'Configurações',
			'Advanced' => 'Avançado',
			'Email' => 'E-mail',
			'History' => 'Histórico',
			'Support' => 'Suporte',
			'Enable Stealth Login' => 'Ativar login oculto',
			'Custom Login URL' => 'URL de login personalizada',
			'Your login page will be at: %s' => 'Sua página de login estará em: %s',
			'Hide wp-admin' => 'Ocultar wp-admin',
			'Block direct access to wp-admin directory' => 'Bloquear acesso direto ao diretório wp-admin',
			'Logout Redirect URL' => 'URL de redirecionamento ao sair',
			'Login URL Preview' => 'Pré-visualização da URL de login',
			'Emergency Recovery URL' => 'URL de recuperação de emergência',
			'Current Recovery URL:' => 'URL de recuperação atual:',
			'Copy URL' => 'Copiar URL',
			'Copied!' => 'Copiado!',
			'Regenerate Recovery URL' => 'Regenerar URL de recuperação',
			'Save Settings' => 'Salvar configurações',
			'XML-RPC & REST API Protection' => 'Proteção XML-RPC e API REST',
			'Disable XML-RPC' => 'Desativar XML-RPC',
			'Brute Force Protection' => 'Proteção contra força bruta',
			'Enable Brute Force Protection' => 'Ativar proteção contra força bruta',
			'Max Login Attempts' => 'Máximo de tentativas de login',
			'Lockout Duration' => 'Duração do bloqueio',
			'minutes' => 'minutos',
			'Login Logging' => 'Registro de logins',
			'Enable Login Logging' => 'Ativar registro de logins',
			'IP Whitelist' => 'Lista branca de IPs',
			'Progressive Lockout' => 'Bloqueio progressivo',
			'Maximum Lockout Duration' => 'Duração máxima do bloqueio',
			'Enable Email Notifications' => 'Ativar notificações por e-mail',
			'Lockout Notifications' => 'Notificações de bloqueio',
			'Login Alerts' => 'Alertas de login',
			'Admin Login Alert' => 'Alerta de login do administrador',
			'New IP Login Alert' => 'Alerta de login de novo IP',
			'Login Attempt History' => 'Histórico de tentativas de login',
			'Filter by IP' => 'Filtrar por IP',
			'Filter by Username' => 'Filtrar por nome de usuário',
			'Filter by Result' => 'Filtrar por resultado',
			'All Results' => 'Todos os resultados',
			'Success' => 'Sucesso',
			'Failed' => 'Falhou',
			'Locked Out' => 'Bloqueado',
			'Whitelisted' => 'Na lista branca',
			'Date From' => 'Data de',
			'Date To' => 'Data até',
			'Filter' => 'Filtrar',
			'Clear Filters' => 'Limpar filtros',
			'No login attempts recorded yet.' => 'Nenhuma tentativa de login registrada ainda.',
			'Showing %1$d to %2$d of %3$d entries' => 'Mostrando %1$d a %2$d de %3$d entradas',
			'%d items' => '%d itens',
			'&laquo; Previous' => '&laquo; Anterior',
			'Next &raquo;' => 'Próximo &raquo;',
			'Clear All Logs' => 'Limpar todos os registros',
			'Export to CSV' => 'Exportar para CSV',
			'All login logs have been cleared.' => 'Todos os registros de login foram limpos.',
			'Frequently Asked Questions' => 'Perguntas frequentes',
			'Need Help?' => 'Precisa de ajuda?',
			'Get Support' => 'Obter suporte',
		),
	),

	// -----------------------------------------------------------------
	// European Portuguese
	// -----------------------------------------------------------------
	'pt_PT' => array(
		'language'     => 'pt_PT',
		'language_name' => 'Portuguese (Portugal)',
		'plural_forms' => 'nplurals=2; plural=(n != 1);',
		'strings'      => array(
			'MSC Stealth Login' => 'MSC Stealth Login',
			'Hide your login page, block brute force attacks, and protect your WordPress site from unauthorized access. Complete free plugin with all features included.' => 'Oculte a sua página de início de sessão, bloqueie ataques de força bruta e proteja o seu site WordPress contra acessos não autorizados. Extensão gratuita completa com todas as funcionalidades.',
			'You do not have permission to access this page.' => 'Não tem permissão para aceder a esta página.',
			'Too many failed login attempts. Please try again in %d minutes.' => 'Demasiadas tentativas de início de sessão falhadas. Tente novamente em %d minutos.',
			'Locked Out' => 'Bloqueado',
			'Access Denied' => 'Acesso negado',
			'IP Address' => 'Endereço IP',
			'Username' => 'Nome de utilizador',
			'Result' => 'Resultado',
			'Date/Time' => 'Data/Hora',
			'Settings updated successfully.' => 'Definições atualizadas com sucesso.',
			'Settings' => 'Definições',
			'Advanced' => 'Avançado',
			'Email' => 'E-mail',
			'History' => 'Histórico',
			'Support' => 'Suporte',
			'Enable Stealth Login' => 'Ativar início de sessão oculto',
			'Custom Login URL' => 'URL de início de sessão personalizado',
			'Your login page will be at: %s' => 'A sua página de início de sessão estará em: %s',
			'Hide wp-admin' => 'Ocultar wp-admin',
			'Logout Redirect URL' => 'URL de redirecionamento ao terminar sessão',
			'Login URL Preview' => 'Pré-visualização do URL de início de sessão',
			'Emergency Recovery URL' => 'URL de recuperação de emergência',
			'Current Recovery URL:' => 'URL de recuperação atual:',
			'Copy URL' => 'Copiar URL',
			'Copied!' => 'Copiado!',
			'Regenerate Recovery URL' => 'Regenerar URL de recuperação',
			'Save Settings' => 'Guardar definições',
			'Brute Force Protection' => 'Proteção contra força bruta',
			'Max Login Attempts' => 'Máximo de tentativas de início de sessão',
			'Lockout Duration' => 'Duração do bloqueio',
			'minutes' => 'minutos',
			'Login Logging' => 'Registo de sessões',
			'IP Whitelist' => 'Lista branca de IPs',
			'Progressive Lockout' => 'Bloqueio progressivo',
			'Enable Email Notifications' => 'Ativar notificações por e-mail',
			'Login Alerts' => 'Alertas de início de sessão',
			'Login Attempt History' => 'Histórico de tentativas de início de sessão',
			'Filter by IP' => 'Filtrar por IP',
			'Filter by Username' => 'Filtrar por nome de utilizador',
			'All Results' => 'Todos os resultados',
			'Success' => 'Sucesso',
			'Failed' => 'Falhado',
			'Locked Out' => 'Bloqueado',
			'Whitelisted' => 'Na lista branca',
			'Date From' => 'Data de',
			'Date To' => 'Data até',
			'Filter' => 'Filtrar',
			'Clear Filters' => 'Limpar filtros',
			'No login attempts recorded yet.' => 'Nenhuma tentativa de início de sessão registada.',
			'Showing %1$d to %2$d of %3$d entries' => 'A mostrar %1$d a %2$d de %3$d entradas',
			'%d items' => '%d itens',
			'&laquo; Previous' => '&laquo; Anterior',
			'Next &raquo;' => 'Seguinte &raquo;',
			'Clear All Logs' => 'Limpar todos os registos',
			'Export to CSV' => 'Exportar para CSV',
			'Frequently Asked Questions' => 'Perguntas frequentes',
			'Need Help?' => 'Precisa de ajuda?',
			'Get Support' => 'Obter suporte',
		),
	),
);

// ==================== PO / MO Generation ====================

// Read POT file to get all msgid entries with their metadata.
$pot_content = file_get_contents( $pot_file );

// Parse POT into entries.
$entries = array();
$current = null;
$current_key = null;

$lines = explode( "\n", $pot_content );
$i = 0;
while ( $i < count( $lines ) ) {
	$line = $lines[ $i ];

	// Comments.
	if ( preg_match( '/^#/', $line ) ) {
		if ( null === $current ) {
			$current = array(
				'comments' => array(),
				'msgid'    => '',
				'msgstr'   => '',
				'msgctxt'  => null,
			);
		}
		$current['comments'][] = $line;
		$i++;
		continue;
	}

	// msgctxt.
	if ( preg_match( '/^msgctxt\s+"(.*)"$/', $line, $m ) ) {
		if ( null === $current ) {
			$current = array( 'comments' => array(), 'msgid' => '', 'msgstr' => '', 'msgctxt' => null );
		}
		$current['msgctxt'] = $m[1];
		$i++;
		continue;
	}

	// msgid (can be multiline).
	if ( preg_match( '/^msgid\s+"(.*)"$/', $line, $m ) ) {
		if ( null === $current ) {
			$current = array( 'comments' => array(), 'msgid' => '', 'msgstr' => '', 'msgctxt' => null );
		}
		$current_key = 'msgid';
		$current['msgid'] = $m[1];
		$i++;
		// Read continuation lines.
		while ( $i < count( $lines ) && preg_match( '/^"(.*)"$/', $lines[ $i ], $m2 ) ) {
			$current['msgid'] .= $m2[1];
			$i++;
		}
		continue;
	}

	// msgstr.
	if ( preg_match( '/^msgstr\s+"(.*)"$/', $line, $m ) ) {
		$current_key = 'msgstr';
		$current['msgstr'] = $m[1];
		$i++;
		// Read continuation lines.
		while ( $i < count( $lines ) && preg_match( '/^"(.*)"$/', $lines[ $i ], $m2 ) ) {
			$current['msgstr'] .= $m2[1];
			$i++;
		}
		// Entry complete.
		$entries[] = $current;
		$current = null;
		$current_key = null;
		continue;
	}

	// Empty line.
	if ( '' === trim( $line ) ) {
		if ( null !== $current && ! empty( $current['msgid'] ) ) {
			$entries[] = $current;
			$current = null;
		}
		$i++;
		continue;
	}

	$i++;
}
if ( null !== $current ) {
	$entries[] = $current;
}

echo "Parsed " . count( $entries ) . " POT entries.\n";

// Resolve inheritance: if a locale's 'strings' is a string (another locale name), copy from that locale.
foreach ( $translations as $locale => $data ) {
	if ( is_string( $data['strings'] ) ) {
		$parent = $data['strings'];
		if ( isset( $translations[ $parent ] ) && is_array( $translations[ $parent ]['strings'] ) ) {
			$translations[ $locale ]['strings'] = $translations[ $parent ]['strings'];
		} else {
			echo "Warning: Parent locale '{$parent}' not found for '{$locale}'\n";
			$translations[ $locale ]['strings'] = array();
		}
	}
}

// Generate PO + MO for each locale.
foreach ( $translations as $locale => $data ) {
	$po_path = $lang_dir . '/msc-stealth-login-' . $locale . '.po';
	$mo_path = $lang_dir . '/msc-stealth-login-' . $locale . '.mo';

	$po_lines = array();
	$po_lines[] = '# Translation of MSC Stealth Login in ' . $data['language_name'];
	$po_lines[] = '# This file is distributed under the GPL-2.0+.';
	$po_lines[] = 'msgid ""';
	$po_lines[] = 'msgstr ""';
	$po_lines[] = '"Project-Id-Version: MSC Stealth Login 1.0.2\n"';
	$po_lines[] = '"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/msc-stealth-login\n"';
	$po_lines[] = '"Last-Translator: Anomalous Developers <info@anomalous.co.za>\n"';
	$po_lines[] = '"Language-Team: ' . $data['language_name'] . ' <info@anomalous.co.za>\n"';
	$po_lines[] = '"Language: ' . $locale . '\n"';
	$po_lines[] = '"MIME-Version: 1.0\n"';
	$po_lines[] = '"Content-Type: text/plain; charset=UTF-8\n"';
	$po_lines[] = '"Content-Transfer-Encoding: 8bit\n"';
	$po_lines[] = '"POT-Creation-Date: 2026-04-25T07:12:23+00:00\n"';
	$po_lines[] = '"PO-Revision-Date: 2026-04-25T07:12:23+00:00\n"';
	$po_lines[] = '"Plural-Forms: ' . $data['plural_forms'] . '\n"';
	$po_lines[] = '"X-Generator: MSC Translation Generator 1.0\n"';
	$po_lines[] = '"X-Domain: msc-stealth-login\n"';
	$po_lines[] = '';

	// Write each entry.
	foreach ( $entries as $entry ) {
		// Skip the header entry (empty msgid).
		if ( '' === $entry['msgid'] ) {
			continue;
		}

		// Decode escaped characters for lookup.
		$raw_msgid = stripcslashes( $entry['msgid'] );

		// Look up translation.
		$msgstr = '';
		if ( isset( $data['strings'][ $raw_msgid ] ) ) {
			$msgstr = $data['strings'][ $raw_msgid ];
		}

		// Write comments.
		foreach ( $entry['comments'] as $comment ) {
			$po_lines[] = $comment;
		}

		// msgctxt if present.
		if ( null !== $entry['msgctxt'] ) {
			$po_lines[] = 'msgctxt "' . $entry['msgctxt'] . '"';
		}

		// Write msgid — preserve original POT formatting.
		$po_lines[] = 'msgid "' . $entry['msgid'] . '"';

		// Write msgstr.
		$escaped_str = addcslashes( $msgstr, "\"\\\n\t" );
		// Handle multiline.
		$escaped_str = str_replace( "\\n", "\\n", $escaped_str );
		$po_lines[] = 'msgstr "' . $escaped_str . '"';
		$po_lines[] = '';
	}

	// Write PO file.
	file_put_contents( $po_path, implode( "\n", $po_lines ) );
	echo "Created: msc-stealth-login-{$locale}.po\n";

	// Compile MO file.
	$cmd = 'msgfmt -o ' . escapeshellarg( $mo_path ) . ' ' . escapeshellarg( $po_path ) . ' 2>&1';
	$output = shell_exec( $cmd );
	if ( file_exists( $mo_path ) ) {
		echo "Created: msc-stealth-login-{$locale}.mo\n";
	} else {
		echo "ERROR compiling MO for {$locale}: {$output}\n";
	}
}

echo "\nDone! Generated translations for " . count( $translations ) . " locales.\n";
