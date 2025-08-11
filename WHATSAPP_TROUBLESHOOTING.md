# WhatsApp Business API Interactive Messages Troubleshooting

## Current Issue: Interactive Messages Not Being Received

Your WhatsApp API calls for interactive messages are successful (status 200, message IDs returned), but recipients are not receiving the messages with RSVP buttons.

## Root Cause: Interactive Messages Need Template Approval

**Problem**: Even interactive messages with buttons require pre-approved templates for first-time users.

## Solution: Create Interactive Message Templates

### Step 1: Create Interactive Template in WhatsApp Business Manager

1. **Go to**: https://business.facebook.com/
2. **Navigate**: WhatsApp > Message Templates
3. **Click**: "Create Template"
4. **Fill in details**:
   - **Template Name**: `event_rsvp_interactive`
   - **Category**: `Marketing` or `Utility`
   - **Language**: `English`
   - **Template Type**: `Interactive`
   - **Interactive Type**: `Button`
   - **Body Text**: `Hello {{1}}, you are invited to {{2}} on {{3}}. Please RSVP:`
   - **Button 1**: `YES` (Reply button)
   - **Button 2**: `NO` (Reply button)  
   - **Button 3**: `MAYBE` (Reply button)
   - **Variables**: 
     - `{{1}}` = Guest Name
     - `{{2}}` = Event Name
     - `{{3}}` = Event Date

### Step 2: Create Donation Interactive Template

- **Template Name**: `donation_interactive`
- **Body Text**: `Hello {{1}}, thank you for your interest in {{2}}. Would you like to make a donation?`
- **Button 1**: `YES` (Reply button)
- **Button 2**: `NO` (Reply button)

### Step 3: Wait for Approval (24-48 hours)

### Step 4: Test with Approved Template

Once approved, test with:

```bash
curl -X POST "https://api.kadirafiki.com/api/webhook/whatsapp/test-interactive-template" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "255762000043", 
    "template": "event_rsvp_interactive",
    "parameters": [
      {"type": "text", "text": "Leon"},
      {"type": "text", "text": "JOHN AND HANNAH WEDDING"},
      {"type": "text", "text": "13/07/2025"}
    ]
  }'
```

## Current Status

✅ **API Integration**: Working correctly  
✅ **Message Sending**: Successful (status 200)  
✅ **Interactive Messages**: API accepts them  
❌ **Message Delivery**: Requires approved templates  

## Immediate Actions

1. **Create interactive templates** in WhatsApp Business Manager
2. **Submit for approval** (24-48 hours wait)
3. **Test with approved templates**
4. **Monitor webhook responses** for RSVP button clicks

## Alternative: Use SMS for Now

While waiting for template approval, you can:

1. **Use SMS notifications** as primary method
2. **Keep WhatsApp integration** ready for when templates are approved
3. **Test with your own number** (you control the opt-in)

## Testing Commands

### Test Interactive Message (Current - Needs Template)
```bash
curl -X POST "http://localhost:8000/api/webhook/whatsapp/test-interactive" \
  -H "Content-Type: application/json" \
  -d '{"phone": "255762000043", "message": "Hello! You are invited to our event. Please RSVP:"}'
```

### Test Regular Message (Fallback)
```bash
curl -X POST "http://localhost:8000/api/webhook/whatsapp/test" \
  -H "Content-Type: application/json" \
  -d '{"phone": "255762000043", "message": "Test message"}'
```

## Next Steps

1. **Create interactive templates** in Business Manager
2. **Wait for approval** (24-48 hours)
3. **Test with approved templates**
4. **Implement RSVP webhook handling** for button clicks

---

**Note**: This is the standard process for WhatsApp Business API interactive messages. All businesses must go through template approval for first-time user contact.
