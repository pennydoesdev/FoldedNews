/**
 * FoldedNews front-end entry.
 *
 * Keep this lean: progressive enhancement only. Interactive newsroom modules
 * (live blog, maps, charts) are code-split and loaded in their own stages.
 */

import './live-blog.js'
import './viz.js'
import './video.js'

import.meta.glob('./**/*.js')
