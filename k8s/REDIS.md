# Connect to Redis CLI
  kubectl exec -it -n flags-api redis-8979f9646-pwp79 -- redis-cli

  # Once inside redis-cli, run these commands:

  # 1. List all session keys (they have prefix 'flags_sess_')
  KEYS flags_sess_*

  # 2. Check how many keys exist
  DBSIZE

  # 3. Look at a specific session's content (replace <session_id> with actual key)
  GET flags_sess_<session_id>

  # 4. Check TTL of a session key
  TTL flags_sess_<session_id>

  # 5. Monitor Redis in real-time (watch new commands coming in)
  MONITOR

  To test the OAuth flow:
  1. Open a terminal with kubectl exec -it -n flags-api redis-8979f9646-pwp79 -- redis-cli MONITOR
  2. In another browser, click "Login to Play" on flags.izeebot.top
  3. Watch Redis - you should see SET flags_sess_... when /login is called
  4. After OAuth redirect back, you should see GET flags_sess_... to retrieve the state

  If the session key is missing on the callback, that confirms the cookie isn't being sent (the Turbo/XHR issue we fixed).
