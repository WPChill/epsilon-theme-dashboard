import './demos.scss';
import Vue from 'vue';
import { EpsilonFetchTranslator } from '../../epsilon-fetch-translator';

declare let wp: any, ajaxurl: string, _: any, jQuery: any;

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
      entrypoint: this.$store.state.entrypoint,
      translations: {
        contentImported: this.$store.state.translations.contentImported,
        lastUpdateOnDemos: this.$store.state.translations.lastUpdateOnDemos,
        waitImport: this.$store.state.translations.waitImport,
        selectImport: this.$store.state.translations.selectImport,
        pluginsFinished: this.$store.state.translations.pluginsFinished,
        installing: this.$store.state.translations.installing,
        activating: this.$store.state.translations.activating,
        import: this.$store.state.translations.import,
        cancel: this.$store.state.translations.cancel,
        select: this.$store.state.translations.select,
        waiting: this.$store.state.translations.waiting,
        completePlugin: this.$store.state.translations.completePlugin,
      },
      MTApiUrl: this.$store.state.mtApiUrl,
      /**
       * Demo flags
       */
      availableDemos: [],
      currentDemo: null,
      demoImporter: [],
      importing: false,
      updatedDemo: null,
      /**
       * Tmp var
       */
      tmp: null,
      /**
       * Filtering
       */
      tags: [],
      selectedTag: null,
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
  computed: {
    importedDemo: function() {
      return this.$store.getters.getImportStatus;
    }
  },
  methods: {
    /**
     * Filter currently selected demos
     */
    filterDemos: function( key: string ) {
      this.selectedTag = key;
    },
    /**
     * Filtering
     * @param {Array<String>} tags
     * @returns {boolean}
     */
    checkTag( tags: Array<String> ) {
      if ( this.selectedTag === null ) {
        return true;
      }

      return _.contains( tags, this.selectedTag );
    },
    /**
     * Import the selected demo
     * @param {string} id
     */
    importDemo: function( id: string ) {
      if ( this.importedDemo ) {
        return;
      }
      this.handleImporting();
    },

    /**
     *
     */
    handleImporting: function() {
      this.importing = true;

      for ( let key in this.demoImporter[ this.currentDemo ] ) {
        this.demoImporter[ this.currentDemo ][ key ].imported = 'queued';
        this.pluginsQueued = true;
      }

      this.startImporting( 0 );
    },

    /**
     *
     * @param now
     */
    startImporting: function( now: number ) {
      let keys = Object.keys( this.demoImporter[ this.currentDemo ] ),
          next = now + 1;

      if ( ! this.demoImporter[ this.currentDemo ][ keys[ now ] ].status ) {
        this.demoImporter[ this.currentDemo ][ keys[ now ] ].imported = 'skipped';
        this.startImporting( next );
        return;
      }

      if ( 'plugins' === this.demoImporter[ this.currentDemo ][ keys[ now ] ].key ) {
        this.tmp = next;
        this.installPlugins( this.currentDemo, keys[ now ] );
        return;
      }

      if ( typeof keys[ next ] === 'undefined' ) {
        this.runAjaxInLoop( this.currentDemo, keys[ now ], now, true );
        return;
      }

      this.runAjaxInLoop( this.currentDemo, keys[ now ], now, false );
    },

    /**
     *
     * @param {number} demoIndex
     * @param {string} contentId
     * @param {number} index
     * @param {boolean} last
     */
    runAjaxInLoop: function( demoIndex: number, contentId: string, index: number, last: boolean ) {
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
        nonce: this.$store.state.ajax_nonce,
        args: {
          action: [ 'Epsilon_Import_Data', 'import_selective_data' ],
          nonce: this.$store.state.ajax_nonce,
          args: {
            id: this.availableDemos[ demoIndex ].id,
            content: temp,
            path: `${this.MTApiUrl}${this.availableDemos[ demoIndex ].id}`
          },
        },
      };

      self.demoImporter[ demoIndex ][ contentId ].imported = 'importing';
      fetchObj = new EpsilonFetchTranslator( data );

      fetch( ajaxurl, fetchObj ).then( function( res ) {
        return res.json();
      } ).then( function( json ) {
        self.handleResult( json, demoIndex, contentId, last );

        if ( ! last ) {
          setTimeout( self.startImporting( index + 1 ), 500 );
        }
      } );
    },

    /**
     * Installs the needed plugins
     *
     * @param {number} demoIndex
     * @param {string} contentId
     */
    installPlugins( demoIndex: number, contentId: string ) {
      this.plugins = this.availableDemos[ demoIndex ].content.plugins.additional;
      this.pluginsCount = this.plugins.length;

      this.installerQueue = setInterval( () => {
        this.plugins.map( ( element: { label: string, slug: string, installed: boolean, active: boolean } ) => {
          this._handlePlugin( demoIndex, contentId, element );
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
      this.removeDupes( 'pluginsInstalled' );
      if ( this.pluginsInstalled.length >= this.pluginsCount ) {
        clearInterval( this.installerQueue );
        this.installerQueue = null;
        this.pluginsFinished = true;
        this.startImporting( this.tmp );
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

      jQuery( document ).on( 'wp-plugin-install-success', ( event: JQuery.Event, response: any ) => {
        this._activatePlugin( response, demoIndex );
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
        nonce: this.$store.state.ajax_nonce,
        args: {
          action: [ 'Epsilon_Dashboard_Helper', 'create_plugin_activation_link' ],
          nonce: this.$store.state.ajax_nonce,
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
      this.pluginAction[ demoIndex ][ slug ] = this.$store.state.translations.installing;
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
          self.pluginAction[ demoIndex ][ response.slug ] = self.translations.activating;
        },
        success: function( res: any ) {
          self.pluginAction[ demoIndex ][ response.slug ] = self.translations.completePlugin;
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
     * @param {boolean} last
     */
    handleResult: function( result: { status: boolean, message: string }, demoIndex: number, contentId: string, last: boolean ) {
      if ( result.status && 'ok' === result.message ) {
        this.demoImporter[ demoIndex ][ contentId ].imported = 'imported';
      }

      if ( ! result.status ) {
        this.demoImporter[ demoIndex ][ contentId ].imported = 'failed';
      }

      if ( last ) {
        this.$store.commit( 'setImportedFlag', true );
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
    },
    /**
     * Removes duplicates
     * @param {string} id
     */
    removeDupes: function( id: string ) {
      this[ id ] = this[ id ].filter( function( item: any, pos: any, ary: any ) {
        return ! pos || item != ary[ pos - 1 ];
      } );
    },
    /**
     * Remove plugins during onboarding, should be installed a step back
     */
    removePlugins: function() {
      let key: string;
      for ( key in this.availableDemos ) {
        for ( let i = 0; i < this.availableDemos[ key ].content.length; i ++ ) {
          if ( 'plugins' === this.availableDemos[ key ].content[ i ].id ) {
            this.availableDemos[ key ].content.splice( i, 1 );
          }
        }
      }
    },
    /**
     * Checks if the demo is installed
     */
    checkAlreadyInstalled: function() {
      const self = this;
      let fetchObj: EpsilonFetchTranslator,
          data = {
            action: 'epsilon_dashboard_ajax_callback',
            nonce: this.$store.state.ajax_nonce,
            args: {
              action: [ 'Epsilon_Dashboard_Helper', 'get_options' ],
              nonce: this.$store.state.ajax_nonce,
              args: {
                theme_mod: this.$store.state.theme[ 'theme-slug' ] + '_content_imported',
              },
            },
          };

      fetchObj = new EpsilonFetchTranslator( data );
      fetch( ajaxurl, fetchObj ).then( function( res ) {
        return res.json();
      } ).then( function( json ) {
        if ( json.status && ('1' === json.value || true === json.value || 'true' === json.value) ) {
          self.$store.commit( 'setImportedFlag', false );
        }
      } );

    }
  },
  /**
   * Template
   */
  template: `
    <div>
      <span style="display:block">{{ translations.lastUpdateOnDemos }} - {{ updatedDemo }}</span>
      <hr />
    
      <nav class="demos-filtering" v-if="tags.length > 1">
          <button class="button button-primary" @click="filterDemos(null)">All</button>
          <button class="button button-primary" v-for="tag in tags" @click="filterDemos(tag)">{{ tag }}</button> 
      </nav>
      <transition-group tag="div" name="demo-complete" class="row" :class="{ epsilonDemoSelected: null !== currentDemo, imported: importedDemo }">
        <div class="col epsilon-demo-box demo-complete-item" v-for="(demo, index) in availableDemos" :key="demo.id" v-if="checkTag(demo.tags) && (null === currentDemo || index === currentDemo)">
          <img :src="demo.thumb" />
          <template v-if="index == currentDemo">
              <template v-if="importedDemo">
                  <p>{{ translations.contentImported }}</p>
              </template>
              <template v-else>
                <p v-if="importing">{{ translations.waitImport }}</p>
                <p v-else>{{ translations.selectImport }}</p>
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
                      <span class="dashicons dashicons-yes"></span> {{ translations.pluginsFinished }}
                    </template>
                    <template v-else>
                      <epsilon-toggle :parent-index="index" :comp-label="content.label" :comp-id="content.id"></epsilon-toggle>
                    </template>
                  </template>
                  <template v-else>
                    <template v-if="wasImported(index, content.id) == 'queued'">
                      <span class="dashicons dashicons-share-alt"></span> {{ content.label }}
                    </template>
                    <template v-else-if="wasImported(index, content.id) == 'importing'">
                      <span class="dashicons dashicons-update epsilon-spin"></span> {{ content.label }}
                    </template>
                    <template v-else-if="wasImported(index, content.id) == 'imported'">
                      <span class="dashicons dashicons-yes"></span> {{ content.label }}
                    </template>
                    <template v-else-if="wasImported(index, content.id) == 'failed'">
                      <span class="dashicons dashicons-warning"></span> {{ content.label }}
                    </template>
                    <template v-else-if="wasImported(index, content.id) == 'skipped'">
                      <span class="dashicons dashicons-sort"></span> {{ content.label }}
                    </template>
                    <template v-else>
                      <epsilon-toggle :parent-index="index" :comp-label="content.label" :comp-id="content.id"></epsilon-toggle>
                    </template>
                  </template>
                </li>
              </ul>
          </template>
          <span class="epsilon-demo-title">{{ demo.label }}</span>
          <template v-if="availableDemos.length > 1">
            <template v-if="index == currentDemo">
                <button class="button button-primary" @click="importDemo(index)" :disabled="importedDemo">{{ translations.import }}</button>
                <button class="button button-link" @click="selectDemo(index)">{{ translations.cancel }}</button>
            </template>
            <template v-else>
                <button class="button button-primary" @click="selectDemo(index)">{{ translations.select }}</button>
            </template>
          </template>
          <template v-else>
            <button class="button button-primary" @click="importDemo(index)" :disabled="importedDemo">{{ translations.import }}</button>
          </template>
        </div>
      </transition-group>
    </div>
  `,
  /**
   * Before mount hook
   */
  beforeMount: function() {
    let temp: any, t1: any;
    this.checkAlreadyInstalled();

    let fetchObj: EpsilonFetchTranslator,
        data = {
          action: 'epsilon_dashboard_ajax_callback',
          nonce: this.$store.state.ajax_nonce,
          args: {
            action: [ 'Epsilon_Dashboard_Helper', 'get_demos' ],
            nonce: this.$store.state.ajax_nonce,
            args: {
              path: this.mtApiUrl,
            },
          },
        };

    fetchObj = new EpsilonFetchTranslator( data );

    fetch( ajaxurl, fetchObj ).then( ( res ) => {
      return res.json();
    } ).then( ( json ) => {
      if ( 'ok' === json.status ) {
        this.updatedDemo = json.demos.updated;
        _.each( json.demos.collection, ( e: any ) => {
          /**
           * Push each demo to the list of available
           */
          this.availableDemos.push( e );
          /**
           * Handle tags
           */
          e.tag.map( ( el: any ) => {
            if ( ! _.contains( this.tags, el ) ) {
              this.tags.push( el );
            }
          } );

          /**
           * Let demo importer know what it should import
           */
          temp = {};
          _.each( e.content, ( content: any, idx: any ) => {
            temp[ idx ] = { key: idx, status: true, imported: false, url: e.url };

            if ( idx === 'plugins' ) {
              t1 = {};
              _.each( content.additional, ( plugin: any ) => {
                t1[ plugin.slug ] = this.translations.waiting;

                if ( plugin.active ) {
                  this.pluginsInstalled.push( plugin.slug );
                  t1[ plugin.slug ] = this.translations.completePlugin;
                }
              } );

              this.pluginAction.push( t1 );
            }
          } );

          this.demoImporter.push( temp );
        } );

        this.removeDupes( 'pluginsInstalled' );

        if ( this.availableDemos.length === 1 ) {
          this.selectDemo( 0 );
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
