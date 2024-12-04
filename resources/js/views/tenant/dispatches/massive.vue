<template>
  <div>
    <div class="page-header pr-0">
      <h2>
        <a href="/dashboard">
          <i class="fas fa-tachometer-alt"></i>
        </a>
      </h2>
      <ol class="breadcrumbs">
        <li class="active">
          <span>Guías de remisión</span>
        </li>
        <li>
          <span class="text-muted">Carga Masiva</span>
        </li>
      </ol>
      <div class="right-wrapper pull-right d-flex">
        <div class="d-flex justify-content-center align-items-center mt-2 mr-2">
          <a class="text-dark" href="/formats/dispatches_massive.xlsx" target="_new">Descargar formato</a>
        </div>
        <div>
          <span>
            <el-upload
              ref="upload"
              :headers="headers"
              action="/dispatches/import_massive_dispatches"
              :show-file-list="false"
              :auto-upload="true"
              :multiple="false"
              :on-error="errorUpload"
              :limit="1"
              :on-success="successUpload"
            >
              <el-button
                slot="trigger"
                type="primary"
                class="btn btn-custom btn-sm mt-2 mr-2"
              >Seleccione un archivo (xlsx)</el-button>
            </el-upload>
          </span>
        </div>
      </div>
    </div>
    <div class="card mb-0 pt-2 pt-md-0">
      <div class="card-header bg-info">
        <h3 class="my-0">Carga Masiva de Guías de Remisión</h3>
      </div>
      <div class="card mb-0">
        <div class="card-body">
          <div>
            <template v-if="showGenerateAllDispatchesButton">
              <div class="row mb-2">
                <div class="col-md-12 col-lg-12 col-xl-12">
                  <el-button
                    class="btn btn-sm"
                    type="primary"
                    @click.prevent="generateAllDispatches"
                  >Generar todas las guías de remisión</el-button>
                </div>
              </div>
            </template>
            <div class="row">
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <th class>Item</th>
                      <th class>F. Emisión</th>
                      <th class>F. Traslado</th>
                      <th class>Serie</th>
                      <th class>Número</th>
                      <th class>Cliente</th>
                      <th class>Detalles</th>
                      <th class>Acciones</th>
                    </thead>
                    <tbody>
                      <tr v-for="(Dispatch, index) in allDispatches">
                        <td class="text-center font-weight-bold">{{ Dispatch.item }}</td>
                        <td>{{ Dispatch.date_of_issue }}</td>
                        <td>{{ Dispatch.date_of_shipping }}</td>
                        <td>{{ Dispatch.additional.series }}</td>
                        <td>{{ Dispatch.number }}</td>
                        <td>{{ Dispatch.additional.customer_name }}</td>
                        <td>
                          <el-button
                            class="btn btn-xs"
                            type="info"
                            icon="el-icon-search"
                            @click.prevent="clickDetails(Dispatch)"
                          ></el-button>
                        </td>
                        <td>
                          <el-button
                            v-if="Dispatch.additional.has_errors == 0 && Dispatch.number == '#'"
                            type="button"
                            style="min-width: 41px"
                            class="btn waves-effect waves-light btn-xs btn-primary m-1__2"
                            :loading="Dispatch.additional.loading_button"
                            @click.prevent="generateDispatch(index)"
                          >Generar</el-button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <dispatch-items :showDialog.sync="showDialogDetails" :data="data" :showClose="true"></dispatch-items>
  </div>
</template>

<script>
import DispatchItems from "./partials/details.vue";

export default {
  components: { DispatchItems },
  data() {
    return {
      showDialogDetails: false,
      showGenerateAllDispatchesButton: false,
      data: {},
      allDispatches: [],
      headers: headers_token,
      titleDialog: null,
      resource: "dispatches",
      errors: {},
      form: {}
    };
  },
  methods: {
    successUpload(response, file, fileList) {
      if (response.success) {
        this.$message.success(response.message);
        this.$refs.upload.clearFiles();
        this.allDispatches = response.data;
        this.showGenerateAllDispatches();
      } else {
        this.$message({ message: response.message, type: "error" });
      }
    },
    errorUpload(response) {
      console.log(response);
    },
    clickDetails(dispatch) {
      this.data = dispatch;
      this.showDialogDetails = true;
    },
    async generateAllDispatches() {
      this.allDispatches.forEach(element => {
        element.additional.loading_button = true;
      });

      for (var i = 0; i < this.allDispatches.length; i++) {
        var actualDispatch = this.allDispatches[i];

        if (actualDispatch.number == "#" && actualDispatch.additional.has_errors == 0) {
          await this.generateDispatch(i);
        }
      }
    },
    async generateDispatch(index) {
      this.allDispatches[index].additional.loading_button = true;
      Document = this.allDispatches[index];

      await this.$http
        .post(`/${this.resource}`, Document)
        .then(response => {
          if (response.data.success) {
            if (response.data.message.startsWith(`Se creo la guía de remisión ${Document.additional.series}-`)) {
              Document.number = response.data.message.replace(`Se creo la guía de remisión ${Document.additional.series}-`, '');
            }

            this.allDispatches[index] == Document;
            this.$message.success(response.data.message);
          } else {
            this.$message.error(response.data.message);
          }
        })
        .catch(error => {
          if (error.response.status === 422) {
            this.errors = error.response.data;
          } else {
            this.$message.error(error.response.data.message);
          }
        })
        .then(() => {
          this.allDispatches[index].additional.loading_button = false;
        });

      this.showGenerateAllDispatches();
    },
    showGenerateAllDispatches() {
      if (this.allDispatches.length > 0) {
        var DispatchesWithErrors = 0;
        this.allDispatches.forEach(element => {
          if (element.additional.has_errors > 0 || element.number != '#') {
            DispatchesWithErrors++;
          }
        });

        if (DispatchesWithErrors > 0) {
          this.showGenerateAllDispatchesButton = false;
        } else {
          this.showGenerateAllDispatchesButton = true;
        }
      } else {
        this.showGenerateAllDispatchesButton = false;
      }
    }
  }
};
</script>