# Changelog

All notable changes to the Laravel Euria package will be documented in this file.

## [1.0.0] - 2024-01-01

### Added
- Initial release
- Text generation (LLM) with Mixtral, Llama, DeepSeek, Mistral support
- Streaming support with SSE
- Embeddings generation
- Image generation (SDXL, Flux)
- Audio transcription (Whisper STT)
- Agent system with Promptable and RemembersConversations traits
- Tool support for Function Calling
- EuriaFake for testing
- Artisan commands: `make:euria-agent`, `euria:models`, `euria:test`
- Events: RequestSent, ResponseReceived, TokensUsed, StreamChunkReceived
- Full test suite with Pest PHP
- GitHub Actions CI/CD
- PHPStan level 9 compliance
- Laravel Pint formatting
