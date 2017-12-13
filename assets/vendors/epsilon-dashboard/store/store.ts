import Vue from 'vue';
import Vuex from 'vuex';

declare let EpsilonDashboard: any;

Vue.use( Vuex );

const state = EpsilonDashboard;

export default new Vuex.Store( {
  state
} );