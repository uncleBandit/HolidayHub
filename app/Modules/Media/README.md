# Provider videos and reels

The Media module keeps provider-authored content separate from the existing
`images` gallery. A `MediaPost` represents a reel or profile video; its original
video and optional cover image are stored as separate `MediaAsset` records.

## Workflow

- Providers manage uploads at `/provider/media`.
- Uploads accept MP4 or WebM videos up to 10 MB and optional JPEG, PNG, or WebP
  cover images up to 3 MB.
- New uploads enter `pending_review` and are not shown publicly.
- Platform admins review the queue at `/admin/media`; approval publishes a
  post, while rejection records a reason visible to the provider.
- Approved reels appear in the keyset-paginated `/reels` stream. Approved reels
  and videos also appear on `/providers/{provider}/media`.

Media defaults to Laravel's private `local` disk. Its temporary signed URLs
allow the browser to play media without making original files public. Set
`MEDIA_DISK` to a configured private cloud disk for deployments that need
persistent, horizontally shared media storage.

The initial implementation supports direct browser playback of uploaded
files. Transcoding, HLS, large direct-to-object-storage uploads, and engagement
tracking are later infrastructure phases.
