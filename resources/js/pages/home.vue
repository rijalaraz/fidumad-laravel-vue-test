<template>
  <card title="Opérations">
    <form @submit.prevent="searchOperation" @keydown="form.onKeydown($event)">
      <div class="form-group row">
        <div class="col-md-4">
          <input v-model="form.rib" class="form-control" type="text" name="rib" placeholder="RIB">
        </div>
        <div class="col-md-3">
          <datepicker v-model="form.minDate" name="minDate" placeholder="Date min" :bootstrap-styling="true"></datepicker>
        </div>
        <div class="col-md-3">
          <datepicker v-model="form.maxDate" name="maxDate" placeholder="Date max" :bootstrap-styling="true"></datepicker>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary">Rechercher</button>
        </div>
      </div>
    </form>

    <div class="card-body">
      <grid :data="gridData" :columns="gridColumns"></grid>
    </div>
  </card>
</template>

<script>
import Form from 'vform'
import Datepicker from 'vuejs-datepicker'
import Grid from './../components/Grid'

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
    async searchOperation () {
      const { data } = await this.form.post('/api/operation')

      this.gridData = data.operations;

      // this.form.reset()
    }
  }

}
</script>
