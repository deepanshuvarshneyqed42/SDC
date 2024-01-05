import { WebRenderer, Args, ComponentAnnotations, AnnotatedStoryFn, StoryAnnotations, StrictArgs, DecoratorFunction, LoaderFunction, StoryContext as StoryContext$1, ProjectAnnotations } from '@storybook/types';

type StoryFnServerReturnType = any;
interface ServerRenderer extends WebRenderer {
    component: string;
    storyResult: StoryFnServerReturnType;
}

/**
 * Metadata to configure the stories for a component.
 *
 * @see [Default export](https://storybook.js.org/docs/formats/component-story-format/#default-export)
 */
type Meta<TArgs = Args> = ComponentAnnotations<ServerRenderer, TArgs>;
/**
 * Story function that represents a CSFv2 component example.
 *
 * @see [Named Story exports](https://storybook.js.org/docs/formats/component-story-format/#named-story-exports)
 */
type StoryFn<TArgs = Args> = AnnotatedStoryFn<ServerRenderer, TArgs>;
/**
 * Story function that represents a CSFv3 component example.
 *
 * @see [Named Story exports](https://storybook.js.org/docs/formats/component-story-format/#named-story-exports)
 */
type StoryObj<TArgs = Args> = StoryAnnotations<ServerRenderer, TArgs>;
/**
 * @deprecated Use `StoryFn` instead.
 * Use `StoryObj` if you want to migrate to CSF3, which uses objects instead of functions to represent stories.
 * You can read more about the CSF3 format here: https://storybook.js.org/blog/component-story-format-3-0/
 *
 * Story function that represents a CSFv2 component example.
 *
 * @see [Named Story exports](https://storybook.js.org/docs/formats/component-story-format/#named-story-exports)
 */
type Story<TArgs = Args> = StoryFn<TArgs>;

type Decorator<TArgs = StrictArgs> = DecoratorFunction<ServerRenderer, TArgs>;
type Loader<TArgs = StrictArgs> = LoaderFunction<ServerRenderer, TArgs>;
type StoryContext<TArgs = StrictArgs> = StoryContext$1<ServerRenderer, TArgs>;
type Preview = ProjectAnnotations<ServerRenderer>;

export { Decorator as D, Loader as L, Meta as M, Preview as P, ServerRenderer as S, StoryFn as a, StoryObj as b, Story as c, StoryContext as d };
