<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        $eventTypes = [
            [
                'name' => 'Wedding',
                'sms_invitation_template' => "Mpendwa {guest_name}, umekaribishwa rasmi kuhudhuria {event_name} tarehe {event_date} mahali: {event_location}. Nambari yako ya mwaliko ni {invite_code}. Tafadhali thibitisha kupitia {rsvp_url}",
                'sms_donation_template' => "Mpendwa {guest_name}, asante kwa kuwa sehemu ya {event_name}. Ili kuunga mkono siku yetu maalum, changia kupitia M-Pesa: {mpesa_number} au Airtel Money: {airtel_number}. Kumbukumbu: {event_name}",
                'whatsapp_invitation_template' => "🎉 *Mwaliko wa Harusi*\n\nMpendwa {guest_name},\n\nUmekaribishwa kuungana nasi kusherehekea siku yetu maalum!\n\n*Tukio:* {event_name}\n*Tarehe:* {event_date}\n*Muda:* {event_time}\n*Mahali:* {event_location}\n\nNambari yako ya mwaliko: `{invite_code}`\n\nTafadhali thibitisha: {rsvp_url}\n\nTunasubiri kusherehekea pamoja nawe! 💒",
                'whatsapp_donation_template' => "💕 *Ombi la Msaada wa Harusi*\n\nMpendwa {guest_name},\n\nAsante kwa upendo na msaada wako!\n\nIkiwa ungependa kuchangia siku yetu maalum, tumia:\n\n*M-Pesa:* {mpesa_number}\n*Airtel Money:* {airtel_number}\n*Kumbukumbu:* {event_name}\n\nMchango wako ni wa thamani kubwa kwetu! 🙏"
            ],
            [
                'name' => 'Birthday',
                'sms_invitation_template' => "Habari {guest_name}! Umekaribishwa kwenye {event_name} tarehe {event_date} mahali: {event_location}. Nambari yako ya mwaliko ni {invite_code}. Thibitisha kupitia {rsvp_url}",
                'sms_donation_template' => "Habari {guest_name}! Kwa ajili ya {event_name}, unaweza kuchangia kupitia M-Pesa: {mpesa_number} au Airtel Money: {airtel_number}. Kumbukumbu: {event_name}. Asante!",
                'whatsapp_invitation_template' => "🎂 *Mwaliko wa Sherehe ya Siku ya Kuzaliwa*\n\nHabari {guest_name}! 🎉\n\nUmekaribishwa kusherehekea pamoja nasi!\n\n*Tukio:* {event_name}\n*Tarehe:* {event_date}\n*Muda:* {event_time}\n*Mahali:* {event_location}\n\nNambari yako ya mwaliko: `{invite_code}`\n\nThibitisha kupitia: {rsvp_url}\n\nTutasherehekea kwa furaha kubwa! 🎈",
                'whatsapp_donation_template' => "🎁 *Msaada wa Siku ya Kuzaliwa*\n\nHabari {guest_name}! 🎉\n\nIkiwa ungependa kuchangia kwa ajili ya {event_name}, tumia:\n\n*M-Pesa:* {mpesa_number}\n*Airtel Money:* {airtel_number}\n*Kumbukumbu:* {event_name}\n\nMchango wako unafanya sherehe kuwa ya kipekee! 🎂"
            ],
            [
                'name' => 'Send-Off',
                'sms_invitation_template' => "Mpendwa {guest_name}, tafadhali jiunge nasi kwa ajili ya {event_name} tarehe {event_date} mahali: {event_location}. Nambari ya mwaliko: {invite_code}. Thibitisha kupitia {rsvp_url}",
                'sms_donation_template' => "Mpendwa {guest_name}, kwa ajili ya {event_name}, unaweza kuchangia kupitia M-Pesa: {mpesa_number} au Airtel Money: {airtel_number}. Kumbukumbu: {event_name}. Asante!",
                'whatsapp_invitation_template' => "👋 *Mwaliko wa Sherehe ya Kuwaga*\n\nMpendwa {guest_name},\n\nJiunge nasi kwa hafla ya kuwaaga wapendwa wetu!\n\n*Tukio:* {event_name}\n*Tarehe:* {event_date}\n*Muda:* {event_time}\n*Mahali:* {event_location}\n\nNambari yako ya mwaliko: `{invite_code}`\n\nThibitisha kupitia: {rsvp_url}\n\nTuwape kuagwa kwa heshima! 🚀",
                'whatsapp_donation_template' => "🚀 *Msaada wa Sherehe ya Kuwaga*\n\nMpendwa {guest_name},\n\nSaidia tukio hili kuwa la kukumbukwa!\n\nUnaweza kuchangia kupitia:\n\n*M-Pesa:* {mpesa_number}\n*Airtel Money:* {airtel_number}\n*Kumbukumbu:* {event_name}\n\nMchango wako ni wa thamani kubwa! 🙏"
            ],
            [
                'name' => 'Baby Shower',
                'sms_invitation_template' => "Habari {guest_name}! Umekaribishwa kwenye {event_name} tarehe {event_date} mahali: {event_location}. Nambari ya mwaliko: {invite_code}. Thibitisha kupitia {rsvp_url}",
                'sms_donation_template' => "Habari {guest_name}! Kwa ajili ya {event_name}, unaweza kuchangia kupitia M-Pesa: {mpesa_number} au Airtel Money: {airtel_number}. Kumbukumbu: {event_name}. Asante!",
                'whatsapp_invitation_template' => "👶 *Mwaliko wa Baby Shower*\n\nHabari {guest_name}! 🍼\n\nUmekaribishwa kusherehekea ujio wa mtoto!\n\n*Tukio:* {event_name}\n*Tarehe:* {event_date}\n*Muda:* {event_time}\n*Mahali:* {event_location}\n\nNambari yako ya mwaliko: `{invite_code}`\n\nThibitisha kupitia: {rsvp_url}\n\nTumpokee mtoto kwa upendo! 💕",
                'whatsapp_donation_template' => "🍼 *Msaada wa Baby Shower*\n\nHabari {guest_name}! 👶\n\nSaidia maandalizi ya mtoto wetu mpendwa!\n\nUnaweza kuchangia kupitia:\n\n*M-Pesa:* {mpesa_number}\n*Airtel Money:* {airtel_number}\n*Kumbukumbu:* {event_name}\n\nAsante kwa mchango wako wa upendo! 💕"
            ],
            [
                'name' => 'Graduation',
                'sms_invitation_template' => "Mpendwa {guest_name}, umekaribishwa kusherehekea {event_name} tarehe {event_date} mahali: {event_location}. Nambari ya mwaliko ni {invite_code}. Thibitisha kupitia {rsvp_url}",
                'sms_donation_template' => "Mpendwa {guest_name}, kwa ajili ya sherehe ya {event_name}, unaweza kuchangia kupitia M-Pesa: {mpesa_number} au Airtel Money: {airtel_number}. Kumbukumbu: {event_name}. Asante sana!",
                'whatsapp_invitation_template' => "🎓 *Mwaliko wa Mahafali*\n\nMpendwa {guest_name},\n\nUmekaribishwa kusherehekea mafanikio haya!\n\n*Tukio:* {event_name}\n*Tarehe:* {event_date}\n*Muda:* {event_time}\n*Mahali:* {event_location}\n\nNambari yako ya mwaliko: `{invite_code}`\n\nThibitisha kupitia: {rsvp_url}\n\nTusherehekee hatua hii muhimu! 🎉",
                'whatsapp_donation_template' => "🎓 *Mchango kwa Mahafali*\n\nMpendwa {guest_name},\n\nTusaidie kusherehekea mafanikio ya kielimu!\n\nUnaweza kuchangia kupitia:\n\n*M-Pesa:* {mpesa_number}\n*Airtel Money:* {airtel_number}\n*Kumbukumbu:* {event_name}\n\nMchango wako ni heshima kubwa kwetu! 🎓"
            ],
            [
                'name' => 'Anniversary',
                'sms_invitation_template' => "Mpendwa {guest_name}, tafadhali jiunge nasi kwenye {event_name} tarehe {event_date} mahali: {event_location}. Nambari ya mwaliko: {invite_code}. Thibitisha kupitia {rsvp_url}",
                'sms_donation_template' => "Mpendwa {guest_name}, kwa ajili ya maadhimisho ya {event_name}, changia kupitia M-Pesa: {mpesa_number} au Airtel Money: {airtel_number}. Kumbukumbu: {event_name}. Tunashukuru sana!",
                'whatsapp_invitation_template' => "💕 *Maadhimisho ya Miaka*\n\nMpendwa {guest_name},\n\nJiunge nasi kusherehekea mapenzi na uaminifu wetu!\n\n*Tukio:* {event_name}\n*Tarehe:* {event_date}\n*Muda:* {event_time}\n*Mahali:* {event_location}\n\nNambari yako ya mwaliko: `{invite_code}`\n\nThibitisha kupitia: {rsvp_url}\n\nTusherehekee upendo wetu! 💑",
                'whatsapp_donation_template' => "💕 *Mchango kwa Maadhimisho*\n\nMpendwa {guest_name},\n\nTusaidie kusherehekea hatua hii ya mapenzi!\n\nUnaweza kuchangia kupitia:\n\n*M-Pesa:* {mpesa_number}\n*Airtel Money:* {airtel_number}\n*Kumbukumbu:* {event_name}\n\nAsante kwa kusherehekea nasi! 💑"
            ],
            [
                'name' => 'Corporate Event',
                'sms_invitation_template' => "Mpendwa {guest_name}, umekaribishwa kwenye {event_name} tarehe {event_date} mahali: {event_location}. Nambari ya mwaliko: {invite_code}. Thibitisha kupitia {rsvp_url}",
                'sms_donation_template' => "Mpendwa {guest_name}, kwa ajili ya {event_name}, changia kupitia M-Pesa: {mpesa_number} au Airtel Money: {airtel_number}. Kumbukumbu: {event_name}. Asante kwa msaada wako!",
                'whatsapp_invitation_template' => "🏢 *Mwaliko wa Tukio la Kampuni*\n\nMpendwa {guest_name},\n\nUmekaribishwa kushiriki tukio letu la kikazi!\n\n*Tukio:* {event_name}\n*Tarehe:* {event_date}\n*Muda:* {event_time}\n*Mahali:* {event_location}\n\nNambari yako ya mwaliko: `{invite_code}`\n\nThibitisha kupitia: {rsvp_url}\n\nTunatarajia kukuona! 📊",
                'whatsapp_donation_template' => "🏢 *Mchango kwa Tukio la Kampuni*\n\nMpendwa {guest_name},\n\nSaidia kuendeleza juhudi zetu za kampuni!\n\nUnaweza kuchangia kupitia:\n\n*M-Pesa:* {mpesa_number}\n*Airtel Money:* {airtel_number}\n*Kumbukumbu:* {event_name}\n\nMchango wako ni muhimu sana! 📊"
            ],
            [
                'name' => 'Conference',
                'sms_invitation_template' => "Mpendwa {guest_name}, umekaribishwa kwenye {event_name} tarehe {event_date} mahali: {event_location}. Nambari ya mwaliko: {invite_code}. Thibitisha kupitia {rsvp_url}",
                'sms_donation_template' => "Mpendwa {guest_name}, kwa ajili ya mkutano wa {event_name}, changia kupitia M-Pesa: {mpesa_number} au Airtel Money: {airtel_number}. Kumbukumbu: {event_name}. Asante!",
                'whatsapp_invitation_template' => "📚 *Mwaliko wa Mkutano*\n\nMpendwa {guest_name},\n\nUmekaribishwa kushiriki mkutano wetu!\n\n*Tukio:* {event_name}\n*Tarehe:* {event_date}\n*Muda:* {event_time}\n*Mahali:* {event_location}\n\nNambari yako ya mwaliko: `{invite_code}`\n\nThibitisha kupitia: {rsvp_url}\n\nTujifunze na kubadilishana mawazo! 🎤",
                'whatsapp_donation_template' => "📚 *Mchango kwa Mkutano*\n\nMpendwa {guest_name},\n\nSaidia kuendeleza maarifa na ujuzi!\n\nUnaweza kuchangia kupitia:\n\n*M-Pesa:* {mpesa_number}\n*Airtel Money:* {airtel_number}\n*Kumbukumbu:* {event_name}\n\nAsante kwa msaada wako! 🎤"
            ],
            [
                'name' => 'Seminar',
                'sms_invitation_template' => "Mpendwa {guest_name}, umekaribishwa kwenye {event_name} tarehe {event_date} mahali: {event_location}. Nambari ya mwaliko: {invite_code}. Thibitisha kupitia {rsvp_url}",
                'sms_donation_template' => "Mpendwa {guest_name}, kwa ajili ya semina ya {event_name}, changia kupitia M-Pesa: {mpesa_number} au Airtel Money: {airtel_number}. Kumbukumbu: {event_name}. Asante!",
                'whatsapp_invitation_template' => "🎓 *Mwaliko wa Semina*\n\nMpendwa {guest_name},\n\nUmekaribishwa kwenye semina yetu ya kielimu!\n\n*Tukio:* {event_name}\n*Tarehe:* {event_date}\n*Muda:* {event_time}\n*Mahali:* {event_location}\n\nNambari yako ya mwaliko: `{invite_code}`\n\nThibitisha kupitia: {rsvp_url}\n\nPanua maarifa yako pamoja nasi! 📖",
                'whatsapp_donation_template' => "🎓 *Mchango kwa Semina*\n\nMpendwa {guest_name},\n\nTusaidie kukuza elimu na maarifa!\n\nUnaweza kuchangia kupitia:\n\n*M-Pesa:* {mpesa_number}\n*Airtel Money:* {airtel_number}\n*Kumbukumbu:* {event_name}\n\nTunakushukuru sana kwa mchango wako! 📖"
            ],
            [
                'name' => 'Workshop',
                'sms_invitation_template' => "Mpendwa {guest_name}, umekaribishwa kwenye {event_name} tarehe {event_date} mahali: {event_location}. Nambari ya mwaliko: {invite_code}. Thibitisha kupitia {rsvp_url}",
                'sms_donation_template' => "Mpendwa {guest_name}, kwa ajili ya warsha ya {event_name}, changia kupitia M-Pesa: {mpesa_number} au Airtel Money: {airtel_number}. Kumbukumbu: {event_name}. Asante!",
                'whatsapp_invitation_template' => "🔧 *Mwaliko wa Warsha*\n\nMpendwa {guest_name},\n\nUmekaribishwa kushiriki warsha ya vitendo!\n\n*Tukio:* {event_name}\n*Tarehe:* {event_date}\n*Muda:* {event_time}\n*Mahali:* {event_location}\n\nNambari yako ya mwaliko: `{invite_code}`\n\nThibitisha kupitia: {rsvp_url}\n\nJifunze na unda nasi! 🛠️",
                'whatsapp_donation_template' => "🔧 *Mchango kwa Warsha*\n\nMpendwa {guest_name},\n\nSaidia kukuza maarifa ya vitendo!\n\nUnaweza kuchangia kupitia:\n\n*M-Pesa:* {mpesa_number}\n*Airtel Money:* {airtel_number}\n*Kumbukumbu:* {event_name}\n\nMchango wako ni muhimu kwa mafanikio! 🛠️"
            ]
        ];

        foreach ($eventTypes as $eventType) {
            EventType::firstOrCreate(
                ['name' => $eventType['name']],
                $eventType
            );
        }
    }
} 