<template>
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
          <button type="submit" class="btn btn-primary">Rechercher</button>
        </div>
      </div>
    </form>
    <grid :data="gridData" :columns="gridColumns"></grid>
  </card>
</template>

<script>
import Form from 'vform'
import Datepicker from 'vuejs-datepicker'
import Grid from './../components/Grid'
import moment from 'moment'
import Swal from 'sweetalert2'
import i18n from '~/plugins/i18n'

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
    gridColumns: ['RIB', 'Date', 'Libelle', 'Montant'],
  }),

  methods: {
    customFormatter(date) {
      return moment(date).format('DD/MM/yyyy');
    },

    selectMinDate(selectedDate) {
      this.form.maxDate = selectedDate;
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

      const { data } = await this.form.post('/api/operation')
      
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
      });

      // this.form.reset()
    }
  }

}
</script>
