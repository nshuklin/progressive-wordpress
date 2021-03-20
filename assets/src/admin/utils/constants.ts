import { ISettings, IPluginStrings, IVapid } from './types';

type cachingStrategy =
  | 'staleWhileRevalidate'
  | 'networkFirst'
  | 'cacheFirst'
  | 'networkOnly';

declare global {
  interface Window {
    pwpJsVars: {
      ajaxUrl: string;
      homeUrl: string;
      adminUrl: string;
      generalError: string;
      pluginPrefix: string;
      settings: ISettings;
      restBase: string;
      restPluginBase: string;
      restPluginNamespace: string;
      pluginStrings: IPluginStrings;
      trackingParamKeys: Array<string>;
      cachingStrategyRoutes: Record<
        string,
        {
          name: string;
          regex: string;
          default: cachingStrategy;
        }
      >;
      cachingStrategies: Record<cachingStrategy, string>;
      nonce: string;
      vapid: IVapid;
      settingsParentKey: string;
      menu: Record<string, string>;
    };
  }
}

export const VARS = window.pwpJsVars;
