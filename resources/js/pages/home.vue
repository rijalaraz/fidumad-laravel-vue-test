<template>
  <div>
    <card title="Opérations">
      <form @submit.prevent="searchOperation" @keydown="form.onKeydown($event)">
        <div class="form-group row">
          <div class="col-md-4">
            <input v-model="form.rib" class="form-control" type="text" name="rib" placeholder="RIB">
          </div>
          <div class="col-md-3">
            <datepicker v-model="form.minDate" name="minDate" placeholder="Date min" @selected="selectMinDate" :bootstrap-styling="true" :format="customFormatter" :use-utc="true"></datepicker>
          </div>
          <div class="col-md-3">
            <datepicker v-model="form.maxDate" name="maxDate" placeholder="Date max" :bootstrap-styling="true" :format="customFormatter" :use-utc="true"></datepicker>
          </div>
          <div class="col-md-2">
            <v-button :loading="form.busy">
                Rechercher
            </v-button>
          </div>
        </div>
      </form>
      <grid :data="gridData" :columns="gridColumns"></grid>
    </card>
    <card>
      <div class="row justify-content-end align-items-center">
        <div class="col-md-auto">
          <button type="button" class="btn btn-secondary" @click="calculateAccountBalance" >Calculer le solde du compte sur cette période</button>
        </div>
        <div class="col-md-3">
          <button type="button" class="btn btn-outline-success btn-block" disabled>{{ accountBalance }}</button>
        </div>
      </div>
    </card>
  </div>

</template>

<script>
import Form from 'vform'
import Datepicker from 'vuejs-datepicker'
import Grid from './../components/Grid'
import moment from 'moment'
import Swal from 'sweetalert2'
import i18n from '~/plugins/i18n'
import accounting from 'accounting'
import getSymbolFromCurrency from 'currency-symbol-map'

export default {
  components: { 
    Datepicker,
    Grid
  },
  middleware: 'auth',

  data: () => ({
    form: new Form({
      minDate: '',
      maxDate: '',
      rib: ''
    }),
    gridData: [],
    gridColumns: ['RIB', 'Date', 'Libelle', 'Montant', 'Recette', 'Dépense'],
    accountBalance: accounting.formatMoney(0, getSymbolFromCurrency('EUR'), 2, ".", ",")
  }),

  methods: {
    customFormatter(date) {
      return moment(date).format('DD/MM/yyyy');
    },

    selectMinDate(selectedDate) {
      if(selectedDate > this.form.maxDate) {
        this.form.maxDate = selectedDate;
      }
    },

    calculateAccountBalance() {
      if( this.gridData.length == 0 ) {
        Swal.fire({
          icon: 'error',
          title: i18n.t('error_alert_title'),
          text: i18n.t('error_alert_text'),
          reverseButtons: true,
          confirmButtonText: i18n.t('ok'),
          cancelButtonText: i18n.t('cancel')
        })
        return false;
      }

      const recetteSum = this.gridData.reduce((a, b) => {
          return a + accounting.unformat(b.Recette, ",");
      },0);

      const depenseSum = this.gridData.reduce((a, b) => {
          return a + accounting.unformat(b.Dépense, ",");
      },0);

      const currency = this.gridData[0].Devise == 'Euro' ? getSymbolFromCurrency('EUR') : getSymbolFromCurrency(this.gridData[0].Devise);

      this.accountBalance = accounting.formatMoney(recetteSum - depenseSum, currency, 2, ".", ",");
    },

    async searchOperation () {

      if( this.form.rib == '' || 
          this.form.minDate == '' || 
          this.form.maxDate == '' || 
          this.form.minDate >  this.form.maxDate 
      ) {
         Swal.fire({
          icon: 'error',
          title: i18n.t('error_alert_title'),
          text: i18n.t('error_alert_text'),
          reverseButtons: true,
          confirmButtonText: i18n.t('ok'),
          cancelButtonText: i18n.t('cancel')
        })
        return false;
      }

      const { data } = await this.form.post('/api/operation');

      this.accountBalance = accounting.formatMoney(0, getSymbolFromCurrency('EUR'), 2, ".", ",");
      
      this.gridData = data.operations.filter(operation => {
        let [day, month, year] = operation.Date.split('/');
        let daty = new Date(year + '-' + month +'-' + day + " UTC");
        return operation.RIB == this.form.rib && daty >= this.form.minDate && daty <= this.form.maxDate;
      }).sort((a, b) => {
        let [dayA, monthA, yearA] = a.Date.split('/');
        let datyA = new Date(yearA + '-' + monthA +'-' + dayA + " UTC");
        let [dayB, monthB, yearB] = b.Date.split('/');
        let datyB = new Date(yearB + '-' + monthB +'-' + dayB + " UTC");
        return datyB - datyA;
      }).map(operation => {
          let currency = operation.Devise == 'Euro' ? getSymbolFromCurrency('EUR') : getSymbolFromCurrency(operation.Devise);
          let montant = accounting.unformat(currency + " " + operation.Montant, ",");
          operation.Recette = montant > 0 ? accounting.formatMoney(montant, currency, 2, ".", ",") : accounting.formatMoney(0, currency, 2, ".", ",");
          operation.Montant = accounting.formatMoney(montant, currency, 2, ".", ",");
          operation.Dépense = montant < 0 ? accounting.formatMoney(Math.abs(montant), currency, 2, ".", ",") : accounting.formatMoney(0, currency, 2, ".", ",");
          return operation;
      });

      // this.form.reset()
    }
  }

}
</script>
