<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Genera PDF de cotización personalizada APPOGIO.
 * Triple idioma. Precios alineados con la regla #11 / personalizedPriceMessage.
 */
class CotizacionPdfService
{
    /**
     * Genera el PDF y retorna la ruta absoluta del archivo guardado en /tmp.
     * Caller debe limpiar el archivo después de enviarlo.
     */
    public function generate(string $clientName, int $qty, ?string $company, ?string $contact, string $lang = 'es'): string
    {
        $tiers = [
            ['plan' => 'INICIA',  'range' => '1–25',    'monthly' => 1.00, 'annual' => 7.00, 'min' => 1,   'max' => 25],
            ['plan' => 'CRECE',   'range' => '26–100',  'monthly' => 1.00, 'annual' => 6.00, 'min' => 26,  'max' => 100],
            ['plan' => 'PRO',     'range' => '101–200', 'monthly' => 0.75, 'annual' => 6.00, 'min' => 101, 'max' => 200],
            ['plan' => 'EMPRESA', 'range' => '200+',    'monthly' => 0.50, 'annual' => 5.00, 'min' => 201, 'max' => 99999],
        ];

        // Marcar tier activo y obtener selected
        $selected = null;
        foreach ($tiers as $i => $t) {
            $isActive = $qty >= $t['min'] && $qty <= $t['max'];
            $tiers[$i]['active'] = $isActive;
            if ($isActive) $selected = $t;
        }
        if (!$selected) $selected = $tiers[0];

        $monthlyTotal = $selected['monthly'] * $qty;
        $annualTotal  = $selected['annual']  * $qty;
        $savingsPct   = $selected['monthly'] > 0
            ? (int) round((1 - ($selected['annual'] / ($selected['monthly'] * 12))) * 100)
            : 0;

        $appFree = $qty >= 200;

        // Logo APPOGIO: leer del options.primary_data.logo configurado en el panel
        $logoPath = null;
        try {
            $opt = \DB::table('options')->where('key', 'primary_data')->value('value');
            $opt = json_decode($opt, true);
            $logoUrl = $opt['logo'] ?? null;
            if ($logoUrl) {
                $relPath = ltrim(parse_url($logoUrl, PHP_URL_PATH) ?? $logoUrl, '/');
                $absPath = public_path($relPath);
                if (is_file($absPath)) $logoPath = $absPath;
            }
        } catch (\Throwable $e) { $logoPath = null; }

        $data = [
            'lang'         => $lang,
            'client_name'  => $clientName ?: '—',
            'company'      => $company,
            'contact'      => $contact,
            'qty'          => $qty,
            'tiers'        => $tiers,
            'selected'     => $selected,
            'monthly_total'=> $monthlyTotal,
            'annual_total' => $annualTotal,
            'savings_pct'  => $savingsPct,
            'app_free'     => $appFree,
            'today'        => Carbon::now()->format('d/m/Y'),
            'valid_until'  => Carbon::now()->addDays(30)->format('d/m/Y'),
            'quote_id'     => Carbon::now()->format('Ymd') . '-' . substr(md5($clientName . $qty), 0, 6),
            't'            => $this->labels($lang),
            'logo_path'    => $logoPath,
            'apps'         => $this->apps($lang),
        ];

        $pdf  = Pdf::loadView('pdf.cotizacion', $data)->setPaper('letter');
        $path = sys_get_temp_dir() . '/cotizacion_' . $data['quote_id'] . '.pdf';
        $pdf->save($path);
        return $path;
    }

    private function labels(string $lang): array
    {
        $labels = [
            'es' => [
                'tag'             => 'Plataforma SaaS marca blanca de rastreo GPS',
                'quote_label'     => 'COTIZACIÓN',
                'valid_label'     => 'Válida hasta',
                'client_label'    => 'Cliente',
                'quantity_label'  => 'Cantidad',
                'units_label'     => 'unidades GPS',
                'platform_title'  => 'La plataforma GPS líder en el mercado global',
                'platform_intro'  => 'APPOGIO es la plataforma GPS marca blanca más completa del mercado. Tecnología de punta desarrollada en Londres, disponible en todo el mundo para que crees y escales tu propio negocio de rastreo bajo tu marca.',
                'years_label'     => 'años de experiencia',
                'years_desc'      => 'en el mercado',
                'companies_label' => 'emprendedores activos',
                'models_label'    => 'modelos GPS compatibles',
                'functions_label' => 'funciones disponibles',
                'bizmodel_title'  => 'Modelo de negocio para distribuidores',
                'bizmodel_desc'   => 'Cobras $5–15 USD/mes por dispositivo a tu cliente final · Pagas a APPOGIO desde $0,50/mes · Margen del 80%+ por dispositivo · Plataforma lista en 24h sin programación ni servidores propios.',
                'pricing_title'   => 'Tabla de precios por volumen',
                'plan_label'      => 'Plan',
                'range_label'     => 'Rango',
                'monthly_label'   => 'Mensual',
                'annual_label'    => 'Anual',
                'your_plan'       => 'tu plan',
                'your_quote'      => 'Tu cotización personalizada',
                'monthly_total'   => 'Pago mensual',
                'annual_total'    => 'Pago anual',
                'per_month'       => 'mes',
                'per_year'        => 'año',
                'save'            => 'ahorras',
                'included_title'  => 'Lo que incluye tu plan',
                'feat_1'          => 'Plataforma 100% marca blanca (TU marca, TU dominio, TU app)',
                'feat_2'          => '+3.800 modelos GPS soportados (Teltonika, Concox, Coban, Queclink…)',
                'feat_3'          => '+80 tipos de informes descargables (PDF, HTML, Excel)',
                'feat_4'          => 'Geocercas inteligentes (polígonos, círculos, KML import/export)',
                'feat_5'          => 'Alertas configurables (velocidad, geocerca, SOS, sensores, motor)',
                'feat_6'          => 'Comandos remotos (cortar/restaurar motor, configuración remota)',
                'feat_7'          => 'API REST + webhooks (Zapier, n8n, ERPNext, Salesforce, CRM)',
                'feat_8'          => 'Sharing público (enlace sin login para clientes finales)',
                'feat_9'          => 'Mapas en tiempo real con historial de recorridos',
                'feat_10'         => 'Multi-usuario con roles (admin, supervisor, operador, conductor)',
                'feat_11'         => 'Capacitaciones, manual de usuario y preguntas frecuentes incluidos',
                'feat_12'         => 'Migración gratuita desde cualquier plataforma GPS',
                'infra_title'     => 'Infraestructura incluida — sin costos ocultos',
                'infra_desc'      => 'El precio incluye servidores propios de alto rendimiento, mapas de Google Maps, red de +3.200 datacenters globales (uptime 99,9%), backups automáticos cifrados y acceso a +80 tipos de informes programables por email.',
                'app_free_title'  => 'App móvil personalizada GRATIS',
                'app_free_desc'   => 'Tu app Android + iOS con tu marca y logo, lista para publicar en Google Play y App Store. Incluida en el plan EMPRESA (+200 unidades).',
                'branded_app_title'    => 'App móvil personalizada con tu marca',
                'branded_app_option'   => 'Modalidad',
                'branded_app_buy'      => 'Compra (pago único)',
                'branded_app_rent'     => 'Renta mensual',
                'branded_app_free_row' => 'Con +100 unidades GPS activas',
                'branded_app_free_val' => 'GRATIS — incluida en el plan',
                'branded_app_note'     => 'App publicada en Google Play y App Store con tu nombre, logo y colores. Conectada a tu plataforma GPS marca blanca. Los usuarios de tu cliente la descargan como si fuera tuya.',
                'apps_title'           => 'Aplicaciones del ecosistema APPOGIO',
                'apps_subtitle'        => 'Todas incluidas en el plan sin costo adicional para distribuidores marca blanca:',
                'app_name_label'       => 'Aplicación',
                'app_desc_label'       => 'Descripción',
                'app_price_label'      => 'Costo',
                'apps_included'        => '✅ Incluida',
                'wa_title'             => 'Integración con WhatsApp Business',
                'wa_desc'         => 'APPOGIO se integra nativamente con WhatsApp: alertas automáticas a tus clientes finales, bot de atención automatizado, envío de reportes, notificaciones de geocerca y mucho más. Sin apps de terceros, sin configuraciones complejas.',
                'payment_title'   => 'Métodos de pago',
                'payment_desc'    => 'Procesamos pagos seguros a nivel mundial:',
                'payment_methods' => 'PayPal (internacional) · Transferencia bancaria · Métodos locales disponibles según país',
                'guarantee_title' => 'Garantía 30 días',
                'guarantee_desc'  => 'Si en los primeros 30 días no estás satisfecho, te devolvemos el 100% de tu dinero sin preguntas.',
                'cta_title'       => '¿Listo para avanzar?',
                'cta_desc'        => 'Escribe DEMO para ver la plataforma en vivo · ASESOR para hablar con un experto · AGENDAR para reservar una demo de 15 min. Activación en 24-48h.',
                'terms_title'     => 'Términos:',
                'terms_1'         => '• Precios en USD por unidad activa. Sin costo de activación, sin setup fee, sin permanencia mínima.',
                'terms_2'         => '• Activación de plataforma en 24-48h tras confirmación de pago.',
                'terms_3'         => '• Capacitaciones, manual de usuario y preguntas frecuentes incluidos. Sin contratos. Sin letra chica.',
                'footer_brand'    => 'Plataforma GPS marca blanca · +11 años · Presencia global',
                'footer_offices'  => 'Oficinas: Londres · París · Minsk · Moscú · Dubái · Seúl · Nueva Delhi · Buenos Aires · Bogotá · Boston',
            ],
            'en' => [
                'tag'             => 'White-label SaaS GPS tracking platform',
                'quote_label'     => 'QUOTE',
                'valid_label'     => 'Valid until',
                'client_label'    => 'Client',
                'quantity_label'  => 'Quantity',
                'units_label'     => 'GPS units',
                'platform_title'  => 'The leading GPS platform in the global market',
                'platform_intro'  => 'APPOGIO is the most complete white-label GPS platform on the market. Cutting-edge technology developed in London, available worldwide so you can create and scale your own tracking business under your brand.',
                'years_label'     => 'years of experience',
                'years_desc'      => 'in the market',
                'companies_label' => 'active businesses',
                'models_label'    => 'compatible GPS models',
                'functions_label' => 'available features',
                'bizmodel_title'  => 'Business model for distributors',
                'bizmodel_desc'   => 'Charge $5–15 USD/month per device to your end client · Pay APPOGIO from $0.50/month · 80%+ margin per device · Platform ready in 24h with no coding or own servers.',
                'pricing_title'   => 'Volume pricing table',
                'plan_label'      => 'Plan',
                'range_label'     => 'Range',
                'monthly_label'   => 'Monthly',
                'annual_label'    => 'Annual',
                'your_plan'       => 'your plan',
                'your_quote'      => 'Your personalized quote',
                'monthly_total'   => 'Monthly payment',
                'annual_total'    => 'Annual payment',
                'per_month'       => 'month',
                'per_year'        => 'year',
                'save'            => 'save',
                'included_title'  => 'What your plan includes',
                'feat_1'          => '100% white-labeled platform (YOUR brand, YOUR domain, YOUR app)',
                'feat_2'          => '+3,800 GPS models supported (Teltonika, Concox, Coban, Queclink…)',
                'feat_3'          => '+80 downloadable report types (PDF, HTML, Excel)',
                'feat_4'          => 'Smart geofences (polygons, circles, KML import/export)',
                'feat_5'          => 'Configurable alerts (speed, geofence, SOS, sensors, engine)',
                'feat_6'          => 'Remote commands (cut/restore engine, remote configuration)',
                'feat_7'          => 'REST API + webhooks (Zapier, n8n, ERPNext, Salesforce, CRM)',
                'feat_8'          => 'Public sharing (login-free link for end clients)',
                'feat_9'          => 'Real-time maps with route history',
                'feat_10'         => 'Multi-user with roles (admin, supervisor, operator, driver)',
                'feat_11'         => 'Training, user manual and FAQ included',
                'feat_12'         => 'Free migration from any GPS platform',
                'infra_title'     => 'Infrastructure included — no hidden costs',
                'infra_desc'      => 'Price includes high-performance own servers, Google Maps, +3,200 global datacenters network (99.9% uptime), encrypted automatic backups, and +80 email-schedulable report types.',
                'app_free_title'  => 'Custom mobile app FREE',
                'app_free_desc'   => 'Your Android + iOS app with your brand, ready for Google Play and App Store. Included in ENTERPRISE plan (+200 units).',
                'branded_app_title'    => 'Custom mobile app with your brand',
                'branded_app_option'   => 'Option',
                'branded_app_buy'      => 'Purchase (one-time)',
                'branded_app_rent'     => 'Monthly rental',
                'branded_app_free_row' => 'With 100+ active GPS units',
                'branded_app_free_val' => 'FREE — included in plan',
                'branded_app_note'     => 'App published on Google Play and App Store with your name, logo and colors. Connected to your white-label GPS platform. Your end clients download it as if it were yours.',
                'apps_title'           => 'APPOGIO Ecosystem Applications',
                'apps_subtitle'        => 'All included at no extra cost for white-label distributors:',
                'app_name_label'       => 'Application',
                'app_desc_label'       => 'Description',
                'app_price_label'      => 'Cost',
                'apps_included'        => '✅ Included',
                'wa_title'             => 'WhatsApp Business Integration',
                'wa_desc'         => 'APPOGIO integrates natively with WhatsApp: automatic alerts to end clients, automated bot, report delivery, geofence notifications and more. No third-party apps, no complex setup.',
                'payment_title'   => 'Payment methods',
                'payment_desc'    => 'We process secure payments worldwide:',
                'payment_methods' => 'PayPal (international) · Bank transfer · Local payment methods available per country',
                'guarantee_title' => '30-day guarantee',
                'guarantee_desc'  => 'If you are not satisfied within the first 30 days, we refund 100% of your money, no questions asked.',
                'cta_title'       => 'Ready to move forward?',
                'cta_desc'        => 'Write DEMO to see the platform live · ADVISOR to talk to an expert · SCHEDULE to book a 15-min demo. Activation in 24-48h.',
                'terms_title'     => 'Terms:',
                'terms_1'         => '• Prices in USD per active unit. No activation fee, no setup fee, no minimum commitment.',
                'terms_2'         => '• Platform activation 24-48h after payment confirmation.',
                'terms_3'         => '• Training, user manual and FAQ included. No contracts. No fine print.',
                'footer_brand'    => 'White-label GPS platform · 11+ years · Global presence',
                'footer_offices'  => 'Offices: London · Paris · Minsk · Moscow · Dubai · Seoul · New Delhi · Buenos Aires · Bogotá · Boston',
            ],
            'pt' => [
                'tag'             => 'Plataforma SaaS marca branca de rastreamento GPS',
                'quote_label'     => 'COTAÇÃO',
                'valid_label'     => 'Válida até',
                'client_label'    => 'Cliente',
                'quantity_label'  => 'Quantidade',
                'units_label'     => 'unidades GPS',
                'platform_title'  => 'A plataforma GPS líder no mercado global',
                'platform_intro'  => 'APPOGIO é a plataforma GPS marca branca mais completa do mercado. Tecnologia de ponta desenvolvida em Londres, disponível em todo o mundo para que você crie e escale seu próprio negócio de rastreamento sob sua marca.',
                'years_label'     => 'anos de experiência',
                'years_desc'      => 'no mercado',
                'companies_label' => 'negócios ativos',
                'models_label'    => 'modelos GPS compatíveis',
                'functions_label' => 'funcionalidades disponíveis',
                'bizmodel_title'  => 'Modelo de negócio para distribuidores',
                'bizmodel_desc'   => 'Cobras $5–15 USD/mês por dispositivo do cliente final · Pagas à APPOGIO a partir de $0,50/mês · Margem de 80%+ por dispositivo · Plataforma pronta em 24h sem programação.',
                'pricing_title'   => 'Tabela de preços por volume',
                'plan_label'      => 'Plano',
                'range_label'     => 'Faixa',
                'monthly_label'   => 'Mensal',
                'annual_label'    => 'Anual',
                'your_plan'       => 'seu plano',
                'your_quote'      => 'Sua cotação personalizada',
                'monthly_total'   => 'Pagamento mensal',
                'annual_total'    => 'Pagamento anual',
                'per_month'       => 'mês',
                'per_year'        => 'ano',
                'save'            => 'economiza',
                'included_title'  => 'O que seu plano inclui',
                'feat_1'          => 'Plataforma 100% marca branca (SUA marca, SEU domínio, SEU app)',
                'feat_2'          => '+3.800 modelos GPS suportados (Teltonika, Concox, Coban, Queclink…)',
                'feat_3'          => '+80 tipos de relatórios para download (PDF, HTML, Excel)',
                'feat_4'          => 'Cercas virtuais inteligentes (polígonos, círculos, KML)',
                'feat_5'          => 'Alertas configuráveis (velocidade, cerca, SOS, sensores, motor)',
                'feat_6'          => 'Comandos remotos (cortar/restaurar motor, configuração remota)',
                'feat_7'          => 'API REST + webhooks (Zapier, n8n, ERPNext, Salesforce, CRM)',
                'feat_8'          => 'Compartilhamento público (link sem login para clientes finais)',
                'feat_9'          => 'Mapas em tempo real com histórico de percursos',
                'feat_10'         => 'Multi-usuário com papéis (admin, supervisor, operador, motorista)',
                'feat_11'         => 'Treinamentos, manual do usuário e perguntas frequentes incluídos',
                'feat_12'         => 'Migração gratuita de qualquer plataforma GPS',
                'infra_title'     => 'Infraestrutura incluída — sem custos ocultos',
                'infra_desc'      => 'O preço inclui servidores próprios de alto desempenho, Google Maps, rede de +3.200 datacenters globais (uptime 99,9%), backups automáticos criptografados e +80 tipos de relatórios agendáveis por email.',
                'app_free_title'  => 'App móvel personalizado GRÁTIS',
                'app_free_desc'   => 'Seu app Android + iOS com sua marca, pronto para Google Play e App Store. Incluído no plano EMPRESA (+200 unidades).',
                'branded_app_title'    => 'App móvel personalizado com sua marca',
                'branded_app_option'   => 'Modalidade',
                'branded_app_buy'      => 'Compra (pagamento único)',
                'branded_app_rent'     => 'Aluguel mensal',
                'branded_app_free_row' => 'Com +100 unidades GPS ativas',
                'branded_app_free_val' => 'GRÁTIS — incluído no plano',
                'branded_app_note'     => 'App publicado na Google Play e App Store com seu nome, logo e cores. Conectado à sua plataforma GPS marca branca. Seus clientes finais baixam como se fosse seu.',
                'apps_title'           => 'Aplicações do ecossistema APPOGIO',
                'apps_subtitle'        => 'Todas incluídas sem custo adicional para distribuidores marca branca:',
                'app_name_label'       => 'Aplicação',
                'app_desc_label'       => 'Descrição',
                'app_price_label'      => 'Custo',
                'apps_included'        => '✅ Incluída',
                'wa_title'             => 'Integração com WhatsApp Business',
                'wa_desc'         => 'APPOGIO integra-se nativamente com WhatsApp: alertas automáticos para clientes finais, bot automatizado, envio de relatórios, notificações de cerca virtual e muito mais. Sem apps de terceiros, sem configurações complexas.',
                'payment_title'   => 'Métodos de pagamento',
                'payment_desc'    => 'Processamos pagamentos seguros em todo o mundo:',
                'payment_methods' => 'PayPal (internacional) · Transferência bancária · Métodos locais disponíveis por país',
                'guarantee_title' => 'Garantia 30 dias',
                'guarantee_desc'  => 'Se nos primeiros 30 dias você não estiver satisfeito, devolvemos 100% do seu dinheiro sem perguntas.',
                'cta_title'       => 'Pronto para avançar?',
                'cta_desc'        => 'Escreva DEMO para ver a plataforma ao vivo · ASESOR para falar com especialista · AGENDAR para marcar demo de 15 min. Ativação em 24-48h.',
                'terms_title'     => 'Termos:',
                'terms_1'         => '• Preços em USD por unidade ativa. Sem taxa de ativação, sem setup fee, sem fidelidade mínima.',
                'terms_2'         => '• Ativação 24-48h após confirmação do pagamento.',
                'terms_3'         => '• Treinamentos, manual do usuário e perguntas frequentes incluídos. Sem contratos. Sem letras miúdas.',
                'footer_brand'    => 'Plataforma GPS marca branca · +11 anos · Presença global',
                'footer_offices'  => 'Escritórios: Londres · Paris · Minsk · Moscou · Dubai · Seul · Nova Delhi · Buenos Aires · Bogotá · Boston',
            ],
        ];

        return $labels[$lang] ?? $labels['es'];
    }

    private function apps(string $lang): array
    {
        $data = [
            'es' => [
                ['name' => 'Pilotos',     'icon' => '🚗', 'desc' => 'App para conductores: checklist de vehículo, registro de incidencias en ruta, solicitudes de combustible y mantenimiento.'],
                ['name' => 'DriveIQ',     'icon' => '📊', 'desc' => 'Análisis de conducción: puntaje por excesos de velocidad, frenadas bruscas y curvas agresivas; gamificación para mejorar hábitos.'],
                ['name' => 'InspectPro',  'icon' => '📋', 'desc' => 'Inspecciones digitales con fotos, firmas electrónicas y generación automática de informes PDF.'],
                ['name' => 'CamFleet',    'icon' => '📹', 'desc' => 'Cámaras en vivo y grabaciones de video en ruta integradas al rastreo GPS.'],
                ['name' => 'TagRadar',    'icon' => '📡', 'desc' => 'Rastreo de activos sin GPS mediante tags Bluetooth/NFC; ideal para inventario móvil y equipos.'],
                ['name' => 'TrackPhone',  'icon' => '📱', 'desc' => 'Convierte cualquier smartphone en un GPS activo sin hardware adicional.'],
                ['name' => 'Notice',      'icon' => '🔔', 'desc' => 'Envío de comunicados, recordatorios de pago, avisos de suspensión y anuncios directamente a tus clientes dentro de la plataforma GPS.'],
            ],
            'en' => [
                ['name' => 'Pilotos',     'icon' => '🚗', 'desc' => 'Driver app: pre-trip vehicle checklist, incident reporting, fuel and maintenance requests.'],
                ['name' => 'DriveIQ',     'icon' => '📊', 'desc' => 'Driving behavior analysis: scoring for speeding, harsh braking, aggressive cornering; gamification to improve habits.'],
                ['name' => 'InspectPro',  'icon' => '📋', 'desc' => 'Digital inspections with photos, e-signatures, and automatic PDF report generation.'],
                ['name' => 'CamFleet',    'icon' => '📹', 'desc' => 'Live camera viewing and in-route video recordings integrated with GPS tracking.'],
                ['name' => 'TagRadar',    'icon' => '📡', 'desc' => 'GPS-free asset tracking via Bluetooth/NFC tags; ideal for mobile inventory and equipment.'],
                ['name' => 'TrackPhone',  'icon' => '📱', 'desc' => 'Turns any smartphone into an active GPS without additional hardware.'],
                ['name' => 'Notice',      'icon' => '🔔', 'desc' => 'Send communications, payment reminders, suspension notices and announcements directly to your clients inside the GPS platform.'],
            ],
            'pt' => [
                ['name' => 'Pilotos',     'icon' => '🚗', 'desc' => 'App para motoristas: checklist pré-viagem, registro de ocorrências, solicitações de combustível e manutenção.'],
                ['name' => 'DriveIQ',     'icon' => '📊', 'desc' => 'Análise de condução: pontuação por excessos de velocidade, freadas bruscas e curvas agressivas; gamificação para melhorar hábitos.'],
                ['name' => 'InspectPro',  'icon' => '📋', 'desc' => 'Inspeções digitais com fotos, assinaturas eletrônicas e geração automática de relatórios PDF.'],
                ['name' => 'CamFleet',    'icon' => '📹', 'desc' => 'Câmeras ao vivo e gravações de vídeo integradas ao rastreamento GPS.'],
                ['name' => 'TagRadar',    'icon' => '📡', 'desc' => 'Rastreamento de ativos sem GPS via tags Bluetooth/NFC; ideal para inventário móvel e equipamentos.'],
                ['name' => 'TrackPhone',  'icon' => '📱', 'desc' => 'Transforma qualquer smartphone em um GPS ativo sem hardware adicional.'],
                ['name' => 'Notice',      'icon' => '🔔', 'desc' => 'Envio de comunicados, lembretes de pagamento, avisos de suspensão e anúncios diretamente aos seus clientes dentro da plataforma GPS.'],
            ],
        ];

        return $data[$lang] ?? $data['es'];
    }
}
