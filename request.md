https://auth.izeebot.top/oauth2/authorize?client_id=flags_app&redirect_uri=https%3A%2F%2Fapi.flags.izeebot.top/oauth/check&response_type=code&scope=openid


https://auth.izeebot.top/oauth2/authorize?client_id=flags_app&redirect_uri=http%3A%2F%2Flocalhost/oauth/check&response_type=code&scope=openid

https://auth.izeebot.top/oauth2/authorize?client_id=flags_app&redirect_uri=https://3bd5404da6bd.ngrok-free.app/oauth/check&response_type=code&scope=openid

QUICK DEBUG PGSQL QUERY:

psql -U openid_user -d openid_db

UPDATE oauth2_client
SET redirect_uris = 'https://04866a95d62b.ngrok-free.app/oauth/check'
WHERE identifier = 'flags_app';

# Verify
SELECT identifier, redirect_uris FROM oauth2_client WHERE identifier = 'flags_app';

https://04866a95d62b.ngrok-free.app/