<?php
require __DIR__ . '/includes/bootstrap.php';
redirigir(usuario_actual() ? 'inicio.php' : 'login.php');
