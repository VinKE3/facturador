<template>
  <el-dialog
    :title="titleDialog"
    :visible="showDialog"
    width="80%"
    :close-on-click-modal="true"
    :close-on-press-escape="true"
    :show-close="false"
    append-to-body
    top="10vh"
  >
    <div class="row">
      <div class="col-md-12">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <th>#</th>
              <th class>Código</th>
              <th class>Description</th>
              <th class>Cantidad</th>
              <th class>P. Unitario</th>
              <th class>SubTotal</th>
              <th class>Total</th>
            </thead>
            <tbody>
              <!-- <slot v-for="(row, index) in records" :row="row" :index="index"></slot> -->
              <!-- <tr v-for="{item, index} in items"> -->
              <tr v-for="(item, index) in items">
                <td class="font-weight-bold">{{ index + 1 }}</td>
                <td>{{ item.item.internal_id }}</td>
                <td>{{ item.item.description }}</td>
                <td>{{ item.quantity }}</td>
                <td class="text-right">{{ item.unit_price }}</td>
                <td class="text-right">{{ item.total_value }}</td>
                <td class="text-right">{{ item.total }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <span slot="footer" class="dialog-footer">
      <el-button @click="clickClose">Cerrar</el-button>
    </span>
  </el-dialog>
</template>

<script>
export default {
  props: ["showDialog", "items", "showClose"],
  data() {
    return {
      titleDialog: "Detalles del Documento",
      form: {},
      company: {},
      locked_emission: {}
    };
  },
  methods: {
    initForm() {
      this.form = {
        customer_email: null,
        download_pdf: null,
        external_id: null,
        number: null,
        id: null
      };
    },
    clickClose() {
      this.$emit("update:showDialog", false);
      this.initForm();
    }
  }
};
</script>