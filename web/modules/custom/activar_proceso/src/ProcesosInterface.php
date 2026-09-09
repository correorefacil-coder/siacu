<?php
namespace Drupal\activar_proceso;

Interface ProcesosInterface {

  public const INICIADO = "Iniciado";
  public const PROCESO = "En Proceso";
  public const FINALIZADO = "Finalizado";
  public const ACTUALIZADO = "Finalizado - Actualizado";
  public const SUSPENDIDO = "Suspendido";
  public const CANCELADO = "Cancelado";

}

