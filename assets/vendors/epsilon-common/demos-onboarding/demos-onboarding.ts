import './demos-onboarding.scss';
import Vue from 'vue';
import { EpsilonFetchTranslator } from '../../epsilon-fetch-translator';

declare let wp: any, ajaxurl: string, jQuery: any;

export const dashboardDemosOnboarding: any = Vue.extend( {
  /**
   * Demos component
   */
  name: 'demos-onboarding',
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
      /**
       * Demo flags
       */
      availableDemos: [],
      currentDemo: null,
      demoImporter: [],
      importing: false,
    };
  },
  computed: {
    importedDemo: function() {
      return this.$store.getters.getImportStatus;
    }
  },
  methods: {
    /**
     * Import the selected demo
     */
    importDemo: function( args: { action: string, from: number } ) {
      const self = this;

      let id = this.currentDemo;
      let time: number = 0,
          i: number = 0;

      if ( this.importedDemo ) {
        setTimeout(
            function() {
              self.$root.$emit( 'change-step', args );
            }, 150
        );
        return;
      }

      for ( let key in this.demoImporter[ id ] ) {
        if ( ! this.demoImporter[ id ][ key ].status ) {
          continue;
        }

        this.importing = true;
        this.demoImporter[ id ][ key ].imported = 'importing';

        time += 450;
        setTimeout( function() {
          i ++;
          self.runAjaxInLoop( id, key );

          if ( i === self.availableDemos[ id ].content.length ) {
            self.$store.commit( 'setImportedFlag', true );

            setTimeout( function() {
              self.$root.$emit( 'change-step', args );
            }, 450 );
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
        nonce: this.$store.state.ajax_nonce,
        args: {
          action: [ 'Epsilon_Import_Data', 'import_selective_data' ],
          nonce: this.$store.state.ajax_nonce,
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
    <transition-group tag="div" name="demo-complete" class="row" :class="{ epsilonDemoSelected: null !== currentDemo, imported: importedDemo }">
      <div class="col epsilon-demo-box demo-complete-item" v-for="(demo, index) in availableDemos" :key="demo.id" v-if="null === currentDemo || index === currentDemo">
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
              </li>
            </ul>
        </template>
        <span class="epsilon-demo-title">{{ demo.label }}</span>
        <template v-if="availableDemos.length > 1">
          <template v-if="index == currentDemo">
              <button class="button button-link" @click="selectDemo(index)">{{ translations.cancel }}</button>
          </template>
          <template v-else>
              <button class="button button-primary" @click="selectDemo(index)">{{ translations.select }}</button>
          </template>
        </template>
      </div>
    </transition-group>
  `,
  /**
   * Before mount hook
   */
  beforeMount: function() {
    const self = this;
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
              path: this.path,
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
        }

        self.removePlugins();

        if ( self.availableDemos.length === 1 ) {
          self.selectDemo( 0 );
        }
      }
    } );
  },
  /**
   * Handle events coming from epsilon-toggle
   */
  created: function() {
    this.$root.$on( 'changed-epsilon-toggle', this.changeDemoContent );
    this.$root.$on( 'install-demo', this.importDemo );
  },
} );
Vue.component( 'demos-onboarding', dashboardDemosOnboarding );
