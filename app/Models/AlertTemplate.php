<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertTemplate extends Model
{
 protected $fillable = [
 'user_id', 'app_id', 'event_type', 'channel', 'language',
 'template_text', 'is_active', 'extra_destinations',
 ];

 protected $casts = [
 'is_active' => 'boolean',
 'extra_destinations' => 'array',
 ];

 public const EVENT_TYPES = [
 'speeding' => 'Exceso de velocidad',
 'geofence_in' => 'Entrada a geocerca',
 'geofence_out' => 'Salida de geocerca',
 'ignition_on' => 'Motor encendido',
 'ignition_off' => 'Motor apagado',
 'sos' => 'SOS / Emergencia',
 'stop' => 'Vehículo detenido',
 'movement' => 'Movimiento detectado',
 'harsh_acceleration' => 'Aceleración brusca',
 'harsh_brake' => 'Frenado brusco',
 'low_battery' => 'Batería baja',
 'power_off' => 'GPS desconectado',
 'default' => 'Por defecto (cualquier alerta)',
 ];

 public const CHANNELS = [
 'whatsapp' => 'WhatsApp',
 'telegram' => 'Telegram',
 'sms' => 'SMS',
 'email' => 'Email',
 ];

    public const DEFAULT_TEMPLATE = "ALERTA {brand}\n\nUnidad: {unit}\nEvento: {event}\nVelocidad: {speed} km/h\nDirección: {address}\nMapa: {map_link}\nHora: {time}";
}
