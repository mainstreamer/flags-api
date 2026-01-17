 JWKS (JSON Web Key Set) - How It Works                                                                                                               
                                                                                                                                                       
  Why JWKS Is Needed                                                                                                                                   
                                                                                                                                                       
  This API acts as a resource server that validates JWT tokens issued by an external authorization server (auth.izeebot.top). Instead of sharing a     
  static secret key between services (which is a security risk), JWKS provides a secure, standardized way to:                                          
                                                                                                                                                       
  1. Fetch public keys dynamically - The auth server publishes its public keys at a well-known endpoint                                                
  2. Support key rotation - When keys are rotated, no configuration changes are needed in this API                                                     
  3. Maintain separation of concerns - This API only validates tokens, never issues them                                                               
                                                                                                                                                       
  Architecture Flow                                                                                                                                    
                                                                                                                                                       
  ┌─────────────┐     1. Login      ┌─────────────────┐                                                                                                
  │   Client    │ ────────────────► │   Auth Server   │                                                                                                
  │             │ ◄──────────────── │ izeebot.top     │                                                                                                
  └─────────────┘   2. JWT Token    └─────────────────┘                                                                                                
         │                                   │                                                                                                         
         │ 3. Request + JWT                  │                                                                                                         
         ▼                                   │                                                                                                         
  ┌─────────────┐     4. Fetch JWKS          │                                                                                                         
  │  Flags API  │ ◄──────────────────────────┘                                                                                                         
  │  (this app) │   /.well-known/jwks.json                                                                                                             
  └─────────────┘                                                                                                                                      
                                                                                                                                                       
  Key Components                                                                                                                                       
  ┌───────────────────────────┬────────────────────────────────────────────────────┐                                                                   
  │           File            │                      Purpose                       │                                                                   
  ├───────────────────────────┼────────────────────────────────────────────────────┤                                                                   
  │ JwksService.php:26-48     │ Fetches and caches public key from JWKS endpoint   │                                                                   
  ├───────────────────────────┼────────────────────────────────────────────────────┤                                                                   
  │ JwksJwtEncoder.php:31-113 │ Validates incoming JWT tokens using the public key │                                                                   
  ├───────────────────────────┼────────────────────────────────────────────────────┤                                                                   
  │ WarmupJwksCommand.php     │ CLI command to pre-fetch keys during deployment    │                                                                   
  └───────────────────────────┴────────────────────────────────────────────────────┘                                                                   
  How Token Validation Works (JwksJwtEncoder::decode)                                                                                                  
                                                                                                                                                       
  1. Fetch public key from cache (or JWKS endpoint if cache miss)                                                                                      
  2. Parse JWT into header, payload, and signature parts                                                                                               
  3. Validate algorithm - only RS256 is accepted                                                                                                       
  4. Verify signature using OpenSSL with the public key                                                                                                
  5. Handle key rotation - if signature fails, refresh key from JWKS and retry once                                                                    
  6. Check expiration - reject if exp claim is in the past                                                                                             
                                                                                                                                                       
  Key Design Decisions                                                                                                                                 
                                                                                                                                                       
  - No token issuance - encode() throws LogicException (line 21-23)                                                                                    
  - 1-hour cache TTL - reduces external HTTP calls while allowing key rotation                                                                         
  - Automatic key refresh - on signature failure, attempts to fetch fresh key before rejecting                                                         
  - PEM conversion - JWK format is converted to PEM for OpenSSL compatibility                                                                          
                                                                                                                                                       
  Configuration                                                                                                                                        
                                                                                                                                                       
  # JWKS_URI environment variable points to:                                                                                                           
  JWKS_URI=https://auth.izeebot.top/.well-known/jwks.json                                                                                              
                                                                                                                                                       
  # Lexik JWT bundle uses the custom encoder:                                                                                                          
  encoder:                                                                                                                                             
      service: App\Flags\Security\JwksJwtEncoder                                                                                                       
                                                                                                                                                       
  Warmup Command                                                                                                                                       
                                                                                                                                                       
  php bin/console app:jwks:warmup                                                                                                                      
                                                                                                                                                       
  Run during deployment to pre-populate the key cache, avoiding cold-start latency on the first request.                                               
                                                                                                                                                       
  ---                                                                                                                                                  
  This design follows OAuth 2.0 / OpenID Connect best practices where resource servers validate tokens using the authorization server's published      
  public keys.         