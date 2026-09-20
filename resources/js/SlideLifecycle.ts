import { SlideState } from "./slides/Slide.js"

// See https://developer.android.com/guide/components/images/activity_lifecycle.png
export interface SlideLifecycle {
    getState(): SlideState

    onCreate(): void

    onStart(): void

    onResume(): void

    onPause(): void

    onStop(): void

    onRestart(): void

    onDestroy(): void
}