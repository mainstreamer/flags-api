🔧 Continuous Delivery Architecture

  GitHub Workflow (Build & Test)
      ↓
  Build & Push to GHCR
      ↓
  Sign Image with Cosign (keyless, OIDC)
      ↓
  Flux CD Monitors Git Repo
      ↓
  Auto-pulls image (signed, verified)
      ↓
  Deploy to k3s
