import * as _storybook_types from '@storybook/types';
import { Addon_ClientStoryApi, Addon_Loadable } from '@storybook/types';
export { ArgTypes, Args, Parameters, StrictArgs } from '@storybook/types';
import { S as ServerRenderer } from './public-types-af1e4671.js';
export { D as Decorator, L as Loader, M as Meta, P as Preview, c as Story, d as StoryContext, a as StoryFn, b as StoryObj } from './public-types-af1e4671.js';

interface ClientApi extends Addon_ClientStoryApi<ServerRenderer['storyResult']> {
    configure(loader: Addon_Loadable, module: NodeModule): void;
    forceReRender(): void;
    raw: () => any;
}
declare const storiesOf: ClientApi['storiesOf'];
declare const configure: ClientApi['configure'];
declare const raw: ((...args: any[]) => never) | (() => _storybook_types.BoundStory<ServerRenderer>[]);
declare const forceReRender: () => void;

export { ServerRenderer, configure, forceReRender, raw, storiesOf };
