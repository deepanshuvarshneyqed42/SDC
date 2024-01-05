import { RenderContext } from '@storybook/types';
import { a as StoryFn, S as ServerRenderer } from './public-types-af1e4671.js';

declare const render: StoryFn<ServerRenderer>;
declare function renderToCanvas({ id, title, name, showMain, showError, forceRemount, storyFn, storyContext, storyContext: { parameters, args, argTypes }, }: RenderContext<ServerRenderer>, canvasElement: ServerRenderer['canvasElement']): Promise<void>;

declare const parameters: {
    renderer: "server";
};

export { parameters, render, renderToCanvas };
