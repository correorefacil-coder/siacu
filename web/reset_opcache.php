<?php
/**
 * Script para reiniciar la memoria OPcache y limpiar cachés en el servidor.
 */
if (function_exists('opcache_reset')) {
  opcache_reset();
  echo "<h2 style='color: green;'>✓ OPcache de PHP reseteado correctamente en memoria.</h2>";
} else {
  echo "<h2 style='color: orange;'>OPcache no está activo o la función opcache_reset() no está disponible.</h2>";
}

echo "<p>Los archivos PHP recién subidos por FTP ya están siendo leídos directamente desde el disco.</p>";
echo "<p><a href='/admin/config/development/performance'>Ir a Rendimiento para vaciar caché de Drupal</a></p>";
