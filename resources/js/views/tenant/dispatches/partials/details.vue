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
    <div class="row mb-0 mb-md-2">
        <div class="col-md-6">
            <span class="font-weight-bold">Modo de Traslado:</span> {{ data.additional.transport_mode_type_description }}
        </div>
        <div class="col-md-6">
            <span class="font-weight-bold">Motivo de Traslado:</span> {{ data.additional.transfer_reason_type_description }}
        </div>
    </div>
    <div class="row mb-0 mb-md-2">
      <div class="col-md-6">
        <span class="font-weight-bold">Descripción Motivo de Traslado:</span> {{ data.transfer_reason_description }}
      </div>
      <div class="col-md-6">
        <span class="font-weight-bold">Observación:</span> {{ data.observations }}
      </div>
    </div>
    <div class="row mb-0 mb-md-2">
        <div class="col-md-4">
            <span class="font-weight-bold">Unidad de Medida:</span> {{ data.additional.unit_type_description }}
        </div>
        <div class="col-md-4">
            <span class="font-weight-bold">Peso Total:</span> {{ data.total_weight }}
        </div>
        <div class="col-md-4">
            <span class="font-weight-bold">N° Paquetes:</span> {{ data.packages_number }}
        </div>
    </div>
    <div class="row mb-0 mb-md-2">
        <div class="col-md-5">
            <span class="font-weight-bold">Ubigeo Partida:</span> {{ data.origin.address }}
        </div>
        <div class="col-md-7">
            <span class="font-weight-bold">Dirección Partida:</span> {{ data.additional.origin_location_description }}
        </div>
    </div>
    <div class="row mb-0 mb-md-2">
        <div class="col-md-5">
            <span class="font-weight-bold">Ubigeo Llegada:</span> {{ data.delivery.address }}
        </div>
        <div class="col-md-7">
            <span class="font-weight-bold">Dirección Llegada:</span> {{ data.additional.delivery_location_description }}
        </div>
    </div>
    <template v-if="data.dispatcher">
      <div class="row mb-0 mb-md-2">
          <div class="col-md-6">
              <span class="font-weight-bold">Transportista Tipo Doc.:</span> {{ data.additional.dispatcher_identity_document_type_description }}
          </div>
          <div class="col-md-6">
              <span class="font-weight-bold">Transportista Núm. Doc.:</span> {{ data.dispatcher.number }}
          </div>
      </div>
      <div class="row mb-0 mb-md-2">
          <div class="col">
              <span class="font-weight-bold">Transportista Nombre:</span> {{ data.dispatcher.name }}
          </div>
      </div>
    </template>
    <template v-if="data.driver">
      <div class="row mb-0 mb-md-2">
         <div class="col-md-6">
             <span class="font-weight-bold">Conductor Tipo Doc.:</span> {{ data.additional.driver_identity_document_type_description }}
         </div>
         <div class="col-md-6">
             <span class="font-weight-bold">Conductor Núm. Doc.:</span> {{ data.driver.number }}
         </div>
     </div>
     <div class="row mb-0 mb-md-2">
         <div class="col-md-6">
             <span class="font-weight-bold">Conductor Licencia:</span> {{ data.driver.license }}
         </div>
         <div class="col-md-6">
             <span class="font-weight-bold">Placa Vehículo:</span> {{ data.license_plate }}
         </div>
     </div>
    </template>
    <div class="row">
      <div class="col-md-12">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <th>#</th>
              <th class>Código</th>
              <th class>Description</th>
              <th class>Cantidad</th>
            </thead>
            <tbody>
              <!-- <slot v-for="(row, index) in records" :row="row" :index="index"></slot> -->
              <!-- <tr v-for="{item, index} in items"> -->
              <tr v-for="(item, index) in data.items">
                <td class="font-weight-bold">{{ index + 1 }}</td>
                <td>{{ item.internal_id }}</td>
                <td>{{ item.description }}</td>
                <td>{{ item.quantity }}</td>
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
  props: ["showDialog", "data", "showClose"],
  data() {
    return {
      titleDialog: "Detalles de la Guía de Remisión",
    //   form: {},
    //   company: {},
    //   locked_emission: {}
    };
  },
  methods: {
    // initForm() {
    //   this.form = {
    //     customer_email: null,
    //     download_pdf: null,
    //     external_id: null,
    //     number: null,
    //     id: null
    //   };
    // },
    clickClose() {
      this.$emit("update:showDialog", false);
    //   this.initForm();
    }
  }
};
</script>