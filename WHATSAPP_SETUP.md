# WhatsApp Business API Integration Setup

This document explains how to set up WhatsApp Business API integration for the Kadirafiki event management system.

## Prerequisites

1. **WhatsApp Business Account**: You need a WhatsApp Business Account
2. **Facebook Developer Account**: Required to access the WhatsApp Business API
3. **Phone Number**: A verified phone number for your WhatsApp Business account

## Setup Steps

### 1. Create WhatsApp Business App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app or use an existing one
3. Add the "WhatsApp" product to your app
4. Configure your WhatsApp Business Account

### 2. Get API Credentials

1. **Access Token**: Get your permanent access token from the WhatsApp app settings
2. **Phone Number ID**: Find your phone number ID in the WhatsApp app
3. **Webhook Verify Token**: Create a custom verify token for webhook verification

### 3. Environment Variables

Add the following variables to your `.env` file:

```env
# WhatsApp Business API Configuration
WHATSAPP_BASE_URL=https://graph.facebook.com/v18.0
WHATSAPP_ACCESS_TOKEN=your_permanent_access_token_here
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id_here
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_custom_verify_token_here
```

### 4. Webhook Configuration

1. **Webhook URL**: Set your webhook URL to: `https://your-domain.com/api/webhook/whatsapp`
2. **Verify Token**: Use the same token as `WHATSAPP_WEBHOOK_VERIFY_TOKEN`
3. **Webhook Fields**: Subscribe to the following fields:
   - `messages`
   - `message_status`

## Features

### 1. Message Sending
- Send personalized invitation and donation messages
- Support for both SMS and WhatsApp
- Template variable replacement
- Bulk message sending

### 2. RSVP Capture
- Guests can reply with "YES", "NO", or "MAYBE"
- Automatic RSVP status updates
- Confirmation messages sent back to guests

### 3. Message Status Tracking
- Real-time delivery status updates
- Automatic notification status updates
- Failed message handling

## Testing

### Test Message Sending
```bash
curl -X POST "https://your-domain.com/api/events/1/notifications" \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Hello {guest_name}, you are invited to {event_name}",
    "notification_type": "WhatsApp",
    "guest_ids": [1, 2, 3]
  }'
```

## Security Considerations

1. **Webhook Verification**: Always verify webhook signatures
2. **Access Token**: Keep your access token secure and rotate regularly
3. **Rate Limiting**: Implement rate limiting for webhook endpoints
4. **Input Validation**: Validate all incoming webhook data
