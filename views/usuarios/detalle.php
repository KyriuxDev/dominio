<?php
// ── Helpers de conversión ────────────────────────────────────────────────────

// Timestamp Windows (100-ns desde 1601) → fecha legible
function winTs($val) {
    if (!$val || $val == '0' || $val == '9223372036854775807') return 'Nunca';
    $unix = round(((float)$val - 116444736000000000) / 10000000);
    if ($unix <= 0) return 'Nunca';
    $unix += TZ_OFFSET * 3600;
    return date('d/m/Y H:i:s', $unix);
}

// userAccountControl: interpreta los flags principales
function uacFlags($uac) {
    $uac = (int)$uac;
    return array(
        'activa'          => !($uac & 0x0002),   // ACCOUNTDISABLE
        'pwd_no_requerida'=> (bool)($uac & 0x0020), // PASSWD_NOTREQD
        'pwd_no_expira'   => (bool)($uac & 0x10000),// DONT_EXPIRE_PASSWORD
        'bloqueada'       => (bool)($uac & 0x0010), // LOCKOUT
        'pwd_cant_change' => (bool)($uac & 0x0040), // PASSWD_CANT_CHANGE
    );
}

// Leer atributo simple del entry LDAP
function av($e, $k, $def = '') {
    $k = strtolower($k);
    return (isset($e[$k][0]) && $e[$k][0] !== '') ? $e[$k][0] : $def;
}

// Leer atributo multivalor
function avMulti($e, $k) {
    $k = strtolower($k);
    $out = array();
    if (isset($e[$k]['count'])) {
        for ($i = 0; $i < $e[$k]['count']; $i++) {
            $out[] = $e[$k][$i];
        }
    }
    return $out;
}

// Extraer solo el CN= de un DN
function cnOnly($dn) {
    if (preg_match('/^CN=([^,]+)/i', $dn, $m)) return $m[1];
    return $dn;
}

// Fila de la tabla detalle
function row($label, $value) {
    $v = ($value !== '' && $value !== null) ? htmlspecialchars($value) : '<span class="nd">—</span>';
    echo '<tr><td class="dl">' . htmlspecialchars($label) . '</td><td class="dv">' . $v . '</td></tr>';
}

function rowBool($label, $bool) {
    $v = $bool
        ? '<span class="pill pill-si">Sí</span>'
        : '<span class="pill pill-no">No</span>';
    echo '<tr><td class="dl">' . htmlspecialchars($label) . '</td><td class="dv">' . $v . '</td></tr>';
}
?>

<style>
.detalle-wrap { max-width: 960px; margin: 24px auto; padding: 0 16px; }

/* Tarjeta de perfil */
.perfil {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
    padding: 24px;
    display: flex;
    gap: 24px;
    align-items: flex-start;
    margin-bottom: 20px;
}
.perfil-foto {
    width: 100px; height: 100px; border-radius: 50%;
    object-fit: cover; border: 3px solid #003087; flex-shrink: 0;
}
.perfil-placeholder {
    width: 100px; height: 100px; border-radius: 50%;
    background: #d9e1f2; display: flex; align-items: center;
    justify-content: center; font-size: 42px; flex-shrink: 0;
}
.perfil-info h2 { font-size: 20px; color: #003087; margin-bottom: 4px; }
.perfil-info p  { color: #555; font-size: 13px; margin-bottom: 2px; }
.perfil-back { margin-left: auto; }

/* Secciones */
.seccion {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    margin-bottom: 16px;
    overflow: hidden;
}
.sec-header {
    background: #003087; color: #fff;
    padding: 9px 16px; font-weight: bold; font-size: 13px;
}
table.dtable { width: 100%; border-collapse: collapse; }
table.dtable tr:nth-child(even) td { background: #f5f7fb; }
table.dtable td { padding: 7px 14px; border-bottom: 1px solid #eaecf4; font-size: 13px; vertical-align: top; }
td.dl { width: 260px; font-weight: bold; color: #003087; white-space: nowrap; }
td.dv { word-break: break-word; }
.nd  { color: #aaa; font-style: italic; }

/* Pills */
.pill { display: inline-block; font-size: 11px; padding: 2px 10px; border-radius: 12px; font-weight: bold; }
.pill-si { background: #1a7a1a; color: #fff; }
.pill-no { background: #c00;    color: #fff; }

/* Lista de grupos */
ul.grp-list { list-style: none; margin: 0; padding: 0; }
ul.grp-list li {
    padding: 5px 14px;
    border-bottom: 1px dotted #dce3f0;
    font-size: 12px;
}
ul.grp-list li:last-child { border-bottom: none; }

.alert-error {
    background: #fde8e8; border-left: 4px solid #c00;
    padding: 12px 16px; border-radius: 4px; margin-bottom: 16px;
}
</style>

<div class="detalle-wrap">

<?php if ($error): ?>
  <div class="alert-error"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></div>
  <p><a href="javascript:history.back()" class="btn">&larr; Regresar</a></p>

<?php elseif ($entry): ?>

  <?php
    $uac   = av($entry, 'userAccountControl', '0');
    $flags = uacFlags($uac);
    $foto  = av($entry, 'thumbnailphoto', '');
    $grupos = avMulti($entry, 'memberOf');
  ?>

  <!-- ── Tarjeta ── -->
  <div class="perfil">
    <?php if ($foto): ?>
      <img class="perfil-foto"
           src="data:image/jpeg;base64,<?php echo base64_encode($foto); ?>"
           alt="Foto">
    <?php else: ?>
      <div class="perfil-placeholder">&#128100;</div>
    <?php endif; ?>
    <div class="perfil-info">
      <h2><?php echo htmlspecialchars(av($entry, 'displayName', '—')); ?></h2>
      <p><?php echo htmlspecialchars(av($entry, 'title')); ?>
        <?php if (av($entry,'department')): ?>
          &middot; <?php echo htmlspecialchars(av($entry,'department')); ?>
        <?php endif; ?>
      </p>
      <p><?php echo htmlspecialchars(av($entry, 'mail')); ?></p>
      <p><?php echo htmlspecialchars(av($entry, 'company')); ?></p>
    </div>
    <div class="perfil-back">
      <a href="/dominio/?c=usuario&a=lista" class="btn">&larr; Lista</a>
    </div>
  </div>

  <!-- ── 1. Identificación ── -->
  <div class="seccion">
    <div class="sec-header">Identificacion</div>
    <table class="dtable">
      <?php row('Nombre de usuario',   av($entry, 'sAMAccountName')); ?>
      <?php row('Nombre completo',     av($entry, 'displayName')); ?>
      <?php row('Nombre(s)',           av($entry, 'givenName')); ?>
      <?php row('Apellidos',           av($entry, 'sn')); ?>
      <?php row('UPN',                 av($entry, 'userPrincipalName')); ?>
      <?php row('Employee ID',         av($entry, 'employeeID')); ?>
      <?php row('Tipo de trabajador',  av($entry, 'employeeType')); ?>
      <?php row('CURP / Division',     av($entry, 'division')); ?>
    </table>
  </div>

  <!-- ── 2. Comentarios ── -->
  <div class="seccion">
    <div class="sec-header">Descripcion</div>
    <table class="dtable">
      <?php row('Comentario (description)', av($entry, 'description')); ?>
      <?php row('Comentario del usuario (info)', av($entry, 'info')); ?>
      <?php row('Puesto (title)',           av($entry, 'title')); ?>
    </table>
  </div>

  <!-- ── 3. Cuenta ── -->
  <div class="seccion">
    <div class="sec-header">Estado de la cuenta</div>
    <table class="dtable">
      <?php rowBool('Cuenta activa',            $flags['activa']); ?>
      <?php rowBool('Cuenta bloqueada',         $flags['bloqueada']); ?>
      <?php row('La cuenta expira',             winTs(av($entry, 'accountExpires'))); ?>
      <?php row('Conteo de intentos fallidos',  av($entry, 'badPwdCount', '0')); ?>
      <?php row('Ultimo intento fallido',       winTs(av($entry, 'badPasswordTime'))); ?>
      <?php row('Inicio de bloqueo',            winTs(av($entry, 'lockoutTime'))); ?>
      <?php row('Inicio de sesion (lastLogon)', winTs(av($entry, 'lastLogon'))); ?>
      <?php row('Ultima sesion (timestamp)',    winTs(av($entry, 'lastLogonTimestamp'))); ?>
      <?php row('Conteo de inicios de sesion',  av($entry, 'logonCount', '0')); ?>
      <?php row('Estaciones autorizadas',       av($entry, 'userWorkstations', 'Todas')); ?>
      <?php row('Horas de sesion autorizadas',  av($entry, 'logonHours') ? 'Ver valor RAW' : 'Todas'); ?>
    </table>
  </div>

  <!-- ── 4. Contraseña ── -->
  <div class="seccion">
    <div class="sec-header">Contrasena</div>
    <table class="dtable">
      <?php row('Ultimo cambio de contrasena',      winTs(av($entry, 'pwdLastSet'))); ?>
      <?php row('La contrasena expira',             winTs(av($entry, 'msds-userpasswordexpirytimecomputed'))); ?>
      <?php rowBool('Contrasena no expira',         $flags['pwd_no_expira']); ?>
      <?php rowBool('Contrasena requerida',         !$flags['pwd_no_requerida']); ?>
      <?php rowBool('Usuario puede cambiar contrasena', !$flags['pwd_cant_change']); ?>
    </table>
  </div>

  <!-- ── 5. Perfil ── -->
  <div class="seccion">
    <div class="sec-header">Perfil de usuario</div>
    <table class="dtable">
      <?php row('Script de inicio de sesion', av($entry, 'scriptPath')); ?>
      <?php row('Perfil de usuario',          av($entry, 'profilePath')); ?>
      <?php row('Directorio principal',       av($entry, 'homeDirectory')); ?>
    </table>
  </div>

  <!-- ── 6. Contacto ── -->
  <div class="seccion">
    <div class="sec-header">Contacto y Ubicacion</div>
    <table class="dtable">
      <?php row('Telefono',           av($entry, 'telephoneNumber')); ?>
      <?php row('Extension',          av($entry, 'otherTelephone')); ?>
      <?php row('Correo',             av($entry, 'mail')); ?>
      <?php row('Oficina',            av($entry, 'physicalDeliveryOfficeName')); ?>
      <?php row('Calle',              av($entry, 'streetAddress')); ?>
      <?php row('Ciudad',             av($entry, 'l')); ?>
      <?php row('Estado',             av($entry, 'st')); ?>
      <?php row('Pais',               av($entry, 'co')); ?>
      <?php row('CP',                 av($entry, 'postalCode')); ?>
      <?php row('Delegacion',         av($entry, 'delegacion')); ?>
      <?php row('Sitio',              av($entry, 'extensionAttribute2')); ?>
    </table>
  </div>

  <!-- ── 7. Grupos ── -->
  <?php if (!empty($grupos)): ?>
  <div class="seccion">
    <div class="sec-header">Miembros del grupo (<?php echo count($grupos); ?>)</div>
    <ul class="grp-list">
      <?php foreach ($grupos as $g): ?>
        <li><?php echo htmlspecialchars(cnOnly($g)); ?>
            <span style="color:#aaa;font-size:11px;margin-left:8px">
              <?php echo htmlspecialchars($g); ?>
            </span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

<?php endif; ?>
</div>