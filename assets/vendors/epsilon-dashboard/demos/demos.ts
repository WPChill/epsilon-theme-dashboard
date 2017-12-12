import './demos.scss';
import Vue from 'vue';
import { EpsilonFetchTranslator } from '../../epsilon-fetch-translator';

declare let EpsilonDashboard: any, wp: any, ajaxurl: string, jQuery: any;

/**
 * Multiple demo functionality
 * @type {ExtendedVue<VueConstructor, any, any, any, Record<never, any>>}
 */
export const dashboardDemos: any = Vue.extend( {
  /**
   * Demos component
   */
  name: 'demos',
  /**
   * Demo path
   */
  props: [ 'path' ],
  /**
   * Model
   * @returns {{}}
   */
  data: function() {
    return {
      /**
       * Imported flag,
       */
      imported: false,
      /**
       * Demo flags
       */
      availableDemos: [],
      currentDemo: null,
      demoImporter: [],
      importing: false,
      /**
       * Plugin related models
       */
      installerQueue: null,
      plugins: null,
      pluginsInstalled: [],
      pluginsInstalling: false,
      pluginsFinished: false,
      pluginsQueued: false,
      pluginsCount: 0,
      pluginAction: [],
    };
  },
  methods: {
    /**
     * Import the selected demo
     * @param {string} id
     */
    importDemo: function( id: string ) {
      const self = this;
      let time: number = 0,
          i: number = 0;
      if ( this.imported ) {
        return;
      }

      for ( let key in this.demoImporter[ id ] ) {
        if ( ! this.demoImporter[ id ][ key ].status ) {
          continue;
        }

        this.importing = true;
        this.pluginsQueued = true;
        this.demoImporter[ id ][ key ].imported = 'importing';

        time += 450;
        setTimeout( function() {
          i ++;
          if ( 'plugins' === key ) {
            self.installPlugins( id, key );
            return;
          }

          self.runAjaxInLoop( id, key );

          console.log( self.installerQueue );
          if ( i === self.availableDemos[ id ].content.length && null === self.installerQueue ) {
            self.imported = true;
          }
        }, time );
      }
    },

    /**
     * Runs ajax in the loop
     * @param {number} demoIndex
     * @param {string} contentId
     */
    runAjaxInLoop: function( demoIndex: number, contentId: string ) {
      const self = this;
      let fetchObj: EpsilonFetchTranslator,
          temp: any = {},
          data: {
            action: string,
            nonce: string,
            args: {},
          };

      temp[ contentId ] = self.demoImporter[ demoIndex ][ contentId ];
      data = {
        action: 'epsilon_dashboard_ajax_callback',
        nonce: EpsilonDashboard.ajax_nonce,
        args: {
          action: [ 'Epsilon_Import_Data', 'import_selective_data' ],
          nonce: EpsilonDashboard.ajax_nonce,
          args: {
            id: this.availableDemos[ demoIndex ].id,
            content: temp,
            path: this.path,
          },
        },
      };

      fetchObj = new EpsilonFetchTranslator( data );

      fetch( ajaxurl, fetchObj ).then( function( res ) {
        return res.json();
      } ).then( function( json ) {
        self.handleResult( json, demoIndex, contentId );
      } );
    },

    /**
     * Installs the needed plugins
     *
     * @param {number} demoIndex
     * @param {string} contentId
     */
    installPlugins( demoIndex: number, contentId: string ) {
      const self = this;
      let plugins;

      for ( let i = 0; i < this.availableDemos[ demoIndex ].content.length; i ++ ) {
        if ( contentId === this.availableDemos[ demoIndex ].content[ i ].id ) {
          self.plugins = this.availableDemos[ demoIndex ].content[ i ].additional;
        }
      }

      self.plugins.map( function( element: { label: string, slug: string, installed: boolean, active: boolean } ) {
        self.pluginsCount += 1;
      } );

      self.installerQueue = setInterval( function() {
        self.plugins.map( function( element: { label: string, slug: string, installed: boolean, active: boolean } ) {
          self._handlePlugin( demoIndex, contentId, element );
        } );
      }, 1000 );
    },

    /**
     * Handles single plugin installation
     *
     * @param {number} demoIndex
     * @param {string} contentId
     * @param {} element
     * @private
     */
    _handlePlugin( demoIndex: number, contentId: string, element: { label: string, slug: string, installed: boolean, active: boolean } ) {
      const self = this;
      if ( self.pluginsInstalled.length >= self.pluginsCount ) {
        clearInterval( self.installerQueue );
        self.installerQueue = null;
        self.pluginsFinished = true;
        self.imported = true;
        return;
      }

      if ( this.pluginsInstalled.indexOf( element.slug ) > - 1 ) {
        return;
      }

      if ( this.pluginsInstalling ) {
        return;
      }

      if ( element.active ) {
        return;
      }

      this.pluginsInstalling = true;

      jQuery( document ).on( 'wp-plugin-install-success', function( event: JQueryEventConstructor, response: any ) {
        self._activatePlugin( response, demoIndex );
      } );

      /**
       * Plugin installed and activated
       */
      if ( element.installed && ! element.active ) {
        this._getActivationLink( element.slug, demoIndex );
      }

      if ( ! element.installed ) {
        this._installPlugin( element.slug, demoIndex );
      }
    },

    /**
     * Gets the activation link through AJAX
     *
     * @param {string} slug
     * @param {number} demoIndex
     * @private
     */
    _getActivationLink: function( slug: string, demoIndex: number ) {
      const self = this;
      let fetchObj: EpsilonFetchTranslator,
          data: { action: string, nonce: string, args: {} };

      data = {
        action: 'epsilon_dashboard_ajax_callback',
        nonce: EpsilonDashboard.ajax_nonce,
        args: {
          action: [ 'Epsilon_Dashboard_Helper', 'create_plugin_activation_link' ],
          nonce: EpsilonDashboard.ajax_nonce,
          args: { slug: slug },
        },
      };

      fetchObj = new EpsilonFetchTranslator( data );
      fetch( ajaxurl, fetchObj ).then( function( res ) {
        return res.json();
      } ).then( function( json ) {
        if ( json.url && 'ok' === json.message ) {
          self._activatePlugin( { activateUrl: json.url, slug: slug }, demoIndex );
        }
      } );
    },

    /**
     * Handles plugin installation
     * @param {string} slug
     * @param {number} demoIndex
     *
     * @private
     */
    _installPlugin: function( slug: string, demoIndex: number ) {
      this.pluginAction[ demoIndex ][ slug ] = EpsilonDashboard.translations.installing;
      wp.updates.installPlugin( {
        slug: slug,
      } );
    },

    /**
     * Handles plugin activation
     *
     * @param demoIndex
     * @param response
     */
    _activatePlugin: function( response: any, demoIndex: number ) {
      const self = this;
      jQuery.ajax( {
        async: true,
        type: 'GET',
        dataType: 'html',
        url: response.activateUrl,
        beforeSend: function() {
          self.pluginAction[ demoIndex ][ response.slug ] = EpsilonDashboard.translations.activating;
        },
        success: function( res: any ) {
          self.pluginAction[ demoIndex ][ response.slug ] = EpsilonDashboard.translations.completePlugin;
          self.pluginsInstalled.push( response.slug );
          self.pluginsInstalling = false;
        }
      } );
    },

    /**
     *
     * @param {} result
     * @param {number} demoIndex
     * @param {string} contentId
     */
    handleResult: function( result: { status: boolean, message: string }, demoIndex: number, contentId: string ) {
      if ( result.status && 'ok' === result.message ) {
        this.demoImporter[ demoIndex ][ contentId ].imported = 'imported';
      }

      if ( ! result.status ) {
        this.demoImporter[ demoIndex ][ contentId ].imported = 'failed';
      }
    },

    /**
     * Toggle the advanced state of the demo importer
     * @param {number} index
     */
    selectDemo: function( index: number ) {
      if ( this.currentDemo === index ) {
        this.currentDemo = null;
        return;
      }

      this.currentDemo = index;
    },
    /**
     * Changes what we should import from the json
     */
    changeDemoContent: function( obj: { id: string, status: boolean, parentIndex: number } ) {
      if ( 'undefined' === typeof this.demoImporter[ obj.parentIndex ] ) {
        return;
      }

      if ( 'undefined' === typeof this.demoImporter[ obj.parentIndex ][ obj.id ] ) {
        return;
      }

      this.demoImporter[ obj.parentIndex ][ obj.id ].status = obj.status;
    },
    /**
     * Check if the current demo was imported
     *
     * @param {number} demoIndex
     * @param {string} id
     * @returns {boolean}
     */
    wasImported: function( demoIndex: number, id: string ) {
      return this.demoImporter[ demoIndex ][ id ].imported;
    },

    /**
     * Plugin icon
     * @param {string} slug
     */
    pluginIcon: function( slug: string ) {
      if ( this.pluginsInstalled.indexOf( slug ) > - 1 ) {
        return 'yes';
      }

      return 'update';
    }
  },
  /**
   * Template
   */
  template: `
    <transition-group tag="div" name="demo-complete" class="row" :class="{ epsilonDemoSelected: null !== currentDemo }">
      <div class="col epsilon-demo-box demo-complete-item" v-for="(demo, index) in availableDemos" :key="demo.id" v-if="null === currentDemo || index === currentDemo">
        <img :src="demo.thumb" />
        <template v-if="index == currentDemo">
            <template v-if="imported">
                <p>{{ EpsilonDashboard.translations.contentImported }}</p>
            </template>
            <template v-else>
              <p v-if="importing">{{ EpsilonDashboard.translations.waitImport }}</p>
              <p v-else>{{ EpsilonDashboard.translations.selectImport }}</p>
            </template>
            
            <ul class="epsilon-demo-box--advanced-list" v-if="index == currentDemo">
              <li v-for="content in demo.content" :key="content.id">
                <template v-if="content.id === 'plugins'">
                  <template v-if="pluginsQueued">
                      <span class="epsilon-plugins-list" v-for="plugin in content.additional">
                        <span class="dashicons" :class="'dashicons-' + pluginIcon(plugin.slug)"></span> {{ pluginAction[index][plugin.slug] }} - {{ plugin.label }}
                        <br />
                      </span>
                  </template>
                  <template v-else-if="pluginsFinished">
                    <span class="dashicons dashicons-yes"></span> {{ EpsilonDashboard.translations.pluginsFinished }}
                  </template>
                  <template v-else>
                    <epsilon-toggle :parent-index="index" :comp-label="content.label" :comp-id="content.id"></epsilon-toggle>
                  </template>
                </template>
                <template v-else>
                  <template v-if="wasImported(index, content.id) == 'importing'">
                    <span class="dashicons dashicons-update"></span> {{ content.label }}
                  </template>
                  <template v-else-if="wasImported(index, content.id) == 'imported'">
                    <span class="dashicons dashicons-yes"></span> {{ content.label }}
                  </template>
                  <template v-else-if="wasImported(index, content.id) == 'failed'">
                    <span class="dashicons dashicons-warning"></span> {{ content.label }}
                  </template>
                  <template v-else>
                    <epsilon-toggle :parent-index="index" :comp-label="content.label" :comp-id="content.id"></epsilon-toggle>
                  </template>
                </template>
              </li>
            </ul>
        </template>
        <span class="epsilon-demo-title">{{ demo.label }}</span>
        <template v-if="index == currentDemo">
            <button class="button button-primary" @click="importDemo(index)">{{ EpsilonDashboard.translations.import }}</button>
            <button class="button button-link" @click="selectDemo(index)">{{ EpsilonDashboard.translations.cancel }}</button>
        </template>
        <template v-else>
            <button class="button button-primary" @click="selectDemo(index)">{{ EpsilonDashboard.translations.select }}</button>
        </template>
      </div>
    </div>
  `,
  /**
   * Before mount hook
   */
  beforeMount: function() {
    const self = this;
    let temp: any, t1: any, t2: any;

    let fetchObj: EpsilonFetchTranslator,
        data = {
          action: 'epsilon_dashboard_ajax_callback',
          nonce: EpsilonDashboard.ajax_nonce,
          args: {
            action: [ 'Epsilon_Dashboard_Helper', 'get_demos' ],
            nonce: EpsilonDashboard.ajax_nonce,
            args: {
              path: this.path
            },
          },
        };

    fetchObj = new EpsilonFetchTranslator( data );

    fetch( ajaxurl, fetchObj ).then( function( res ) {
      return res.json();
    } ).then( function( json ) {
      if ( 'ok' === json.status ) {
        for ( let key in json.demos ) {
          self.availableDemos.push( json.demos[ key ] );
          temp = {};
          json.demos[ key ].content.map( function( element: any ) {
            temp[ element.id ] = { status: true, imported: false };
          } );

          self.demoImporter.push( temp );

          for ( let i = 0; i < json.demos[ key ].content.length; i ++ ) {
            if ( 'plugins' === json.demos[ key ].content[ i ].id ) {
              t1 = {};
              for ( let j = 0; j < json.demos[ key ].content[ i ].additional.length; j ++ ) {
                t1[ json.demos[ key ].content[ i ].additional[ j ].slug ] = EpsilonDashboard.translations.waiting;

                if ( json.demos[ key ].content[ i ].additional[ j ].active ) {
                  self.pluginsInstalled.push( json.demos[ key ].content[ i ].additional[ j ].slug );
                  t1[ json.demos[ key ].content[ i ].additional[ j ].slug ] = EpsilonDashboard.translations.completePlugin;
                }
              }
              self.pluginAction.push( t1 );
            }
          }
        }
      }
    } );
  },
  /**
   * Handle events coming from epsilon-toggle
   */
  created: function() {
    this.$root.$on( 'changed-epsilon-toggle', this.changeDemoContent );
  },
} );
Vue.component( 'demos', dashboardDemos );