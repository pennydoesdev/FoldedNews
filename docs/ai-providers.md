# AI Providers

> Status: skeleton. Populated in **Stage 14 (Universal AI Copilot)**.

Provider-routable AI: admins pick provider/model per feature (SEO, summaries,
headlines, newsletters, liveblog cleanup, moderation, tagging, ad intelligence,
transcript cleanup, alt text, reader chatbot, editorial notes). Every output is
logged; every provider is replaceable; fallback providers supported. No
automatic publishing of sensitive editorial output — human approval required.

## Adapters

| Provider | Official docs | Package / API | Auth | Env var |
|---|---|---|---|---|
| OpenAI | https://platform.openai.com/docs | `openai-php/client` | API key | `OPENAI_API_KEY` |
| Anthropic | https://docs.claude.com/ | Messages API (HTTP) | API key | `ANTHROPIC_API_KEY` |
| Google Gemini | https://ai.google.dev/gemini-api/docs | Gemini API (HTTP) | API key | `GEMINI_API_KEY` |
| MiniMax | https://www.minimax.io/platform | OpenAI-compatible HTTP | API key | `MINIMAX_API_KEY` |
| Llama / open-weight | https://www.llama.com/docs/ | OpenAI-compatible endpoint | key/none | `AI_OPENAI_COMPAT_BASE_URL` |
| Puter | https://docs.puter.com/ | Puter API | per docs | `PUTER_*` |
| Featherless AI | https://featherless.ai/ | OpenAI-compatible HTTP | API key | `FEATHERLESS_API_KEY` |
| OpenAI-compatible (custom) | provider-specific | OpenAI schema | API key | `AI_OPENAI_COMPAT_BASE_URL` |

- **Rate limits:** per provider (linked above).
- **Testing:** each provider gets a `--dry-run` health check command and a
  recorded-fixture test; fallback is verified by forcing the primary to fail.

## Classes (Stage 14)

`AiRouter`, `AiProviderInterface`, `OpenAiProvider`, `GeminiProvider`,
`AnthropicProvider`, `MiniMaxProvider`, `PuterProvider`, `FeatherlessProvider`,
`OpenCompatibleProvider`, `AiUsageLogger`, `AiCostEstimator`,
`AiPromptRegistry`, `AiSafetyService`.
