<div class="container">

<?php if ($error): ?>
  <div class="alert-error"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></div>

<?php elseif ($searched && !$error): ?>

  <div class="toolbar">
    <form method="post" action="">
      <button type="submit">Actualizar</button>
    </form>
    <input type="text"
           id="filtro"
           placeholder="Filtrar por nombre, correo, area..."
           onkeyup="filtrarTabla('filtro','tabla-usuarios','contador')">
    <span class="count" id="contador">
      <strong><?php echo count($results); ?></strong> usuario(s)
    </span>
    <?php if ($cacheInfo): ?>
      <span class="cache-info">Consultado: <?php echo $cacheInfo; ?></span>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table id="tabla-usuarios">
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre completo</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Puesto</th>
            <th>Departamento</th>
            <th>Sitio</th>
            <th>Tipo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $idx => $r): ?>
          <?php
            $tipo = strtolower($r['employeeType']);
            if (strpos($tipo, 'confianza') !== false)    $badge = 'badge-confianza';
            elseif (strpos($tipo, 'base') !== false)     $badge = 'badge-base';
            elseif (strpos($tipo, 'eventual') !== false) $badge = 'badge-eventual';
            else                                          $badge = 'badge-otro';
          ?>
          <tr>
            <td><?php echo $idx + 1; ?></td>
            <td><?php echo htmlspecialchars($r['displayName']); ?></td>
            <td><?php echo htmlspecialchars($r['sAMAccountName']); ?></td>
            <td><?php echo htmlspecialchars($r['mail']); ?></td>
            <td><?php echo htmlspecialchars($r['title']); ?></td>
            <td><?php echo htmlspecialchars($r['department']); ?></td>
            <td><?php echo htmlspecialchars($r['extensionAttribute2']); ?></td>
            <td>
              <?php if ($r['employeeType'] !== ''): ?>
                <span class="badge <?php echo $badge; ?>">
                  <?php echo htmlspecialchars($r['employeeType']); ?>
                </span>
              <?php endif; ?>
            </td>
            <td>
              <a class="link-detalle"
                 href="/dominio/?c=usuario&a=detalle&q=<?php echo urlencode($r['sAMAccountName']); ?>">
                Ver
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>
  <div class="welcome">
    <h2>Directorio de Trabajadores - Delegacion Oaxaca</h2>
    <p>Carga todos los usuarios dentro de la OU Oaxaca.</p>
    <form method="post" action="">
      <button type="submit">Cargar directorio</button>
    </form>
  </div>
<?php endif; ?>

</div>