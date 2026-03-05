const crypto = require('crypto');

const ENCRYPTION_KEY = '2r36789f45q01ae5';

function decrypt(base64Data) {
    try {
        const key = Buffer.from(ENCRYPTION_KEY, 'utf8');
        const rawData = Buffer.from(base64Data, 'base64');
        const iv = rawData.slice(0, 16);
        const ciphertext = rawData.slice(16);

        const decipher = crypto.createDecipheriv('aes-128-cbc', key, iv);
        let decrypted = decipher.update(ciphertext, 'binary', 'utf8');
        decrypted += decipher.final('utf8');
        return JSON.parse(decrypted);
    } catch (e) {
        console.error('Decryption failed:', e.message);
        return null;
    }
}

async function check() {
    const fetch = (await import('node-fetch')).default;
    const url = 'https://api.mydramawave.com/h5-api/search/hot-list';
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Platform': 'h5',
            'Version': '1.0.0',
            'App-Name': 'com.dramawave.h5',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    });
    const text = await response.text();
    console.log('Response length:', text.length);
    const data = decrypt(text);
    if (data) {
        console.log('Decrypted data sample:', JSON.stringify(data).substring(0, 500));
        const items = data.data.list || data.data.items || [];
        if (items.length > 0) {
            console.log('First item ID:', items[0].playlet_id || items[0].id || items[0].series_id);
        }
    } else {
        console.log('Failed to decrypt. Trying to see if it is plain JSON.');
        try {
            const jsonData = JSON.parse(text);
            console.log('Plain JSON sample:', JSON.stringify(jsonData).substring(0, 500));
        } catch (e) {
            console.log('Not plain JSON either.');
        }
    }
}

check();
