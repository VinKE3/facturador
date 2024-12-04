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
          <span>Comprobantes</span>
        </li>
        <li>
          <span class="text-muted">Carga Masiva</span>
        </li>
      </ol>
      <div class="right-wrapper pull-right d-flex">
        <div class="d-flex justify-content-center align-items-center mt-2 mr-2">
          <a
            class="text-dark"
            href="/formats/documents_massive.xlsx"
            target="_new"
            >Descargar formato</a
          >
        </div>
        <div>
          <span>
            <el-upload
              ref="upload"
              :headers="headers"
              action="/documents/import_massive_documents"
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
                >Seleccione un archivo (xlsx)</el-button
              >
            </el-upload>
          </span>
        </div>
      </div>
    </div>
    <div class="card mb-0 pt-2 pt-md-0">
      <div class="card-header bg-info">
        <h3 class="my-0">Carga Masiva de Ventas</h3>
      </div>
      <div class="card mb-0">
        <div class="card-body">
          <div>
            <template v-if="showGenerateAllDocumentsButton">
              <div class="row mb-2">
                <div class="col-md-12 col-lg-12 col-xl-12">
                  <el-button
                    class="btn btn-sm"
                    type="primary"
                    @click.prevent="generateAllDocuments"
                    >Generar todos los documentos</el-button
                  >
                </div>
              </div>
            </template>
            <div class="row">
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <th class>Item</th>
                      <th class>Fecha</th>
                      <th class>Tipo</th>
                      <th class>Serie</th>
                      <th class>Número</th>
                      <th class>Cliente</th>
                      <th class>Total</th>
                      <th class>Detalles</th>
                      <th class>Acciones</th>
                    </thead>
                    <tbody>
                      <tr v-for="(Document, index) in allDocuments">
                        <td class="text-center font-weight-bold">
                          {{ Document.item }}
                        </td>
                        <td>{{ Document.date_of_issue }}</td>
                        <td>
                          {{ Document.document_type_id == "01" ? "FT" : "BV" }}
                        </td>
                        <td>{{ Document.additional.series }}</td>
                        <td>{{ Document.number }}</td>
                        <td>{{ Document.additional.customer_name }}</td>
                        <td class="text-right">{{ Document.total }}</td>
                        <td>
                          <el-button
                            class="btn btn-xs"
                            type="info"
                            icon="el-icon-search"
                            @click.prevent="clickDetails(Document.items)"
                          ></el-button>
                        </td>
                        <td>
                          <el-button
                            v-if="
                              Document.customer_id != null &&
                              !Document.additional.has_null_items &&
                              Document.number == '#'
                            "
                            type="button"
                            style="min-width: 41px"
                            class="
                              btn
                              waves-effect waves-light
                              btn-xs btn-primary
                              m-1__2
                            "
                            :loading="Document.additional.loading_button"
                            @click.prevent="generateDocument(index)"
                            >Generar</el-button
                          >
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

    <document-items
      :showDialog.sync="showDialogDetails"
      :items="items"
      :showClose="true"
    ></document-items>
  </div>
</template>

<script>
import DocumentItems from "./partials/details.vue";

export default {
  components: { DocumentItems },
  data() {
    return {
      showDialogDetails: false,
      showGenerateAllDocumentsButton: false,
      items: [],
      allDocuments: [],
      headers: headers_token,
      titleDialog: null,
      resource: "documents",
      errors: {},
      form: {},
    };
  },
  methods: {
    successUpload(response, file, fileList) {
      if (response.success) {
        this.$message.success(response.message);
        this.$refs.upload.clearFiles();
        this.allDocuments = response.data;
        this.showGenerateAllDocuments();
      } else {
        this.$message({ message: response.message, type: "error" });
      }
    },
    errorUpload(response) {
      console.log(response);
    },
    clickDetails(items) {
      this.items = items;
      this.showDialogDetails = true;
    },
    async generateAllDocuments() {
      this.allDocuments.forEach((element) => {
        element.additional.loading_button = true;
      });

      for (var i = 0; i < this.allDocuments.length; i++) {
        var actualDocument = this.allDocuments[i];

        if (
          actualDocument.customer_id != null &&
          !actualDocument.additional.has_null_items &&
          actualDocument.number == "#"
        ) {
          await this.generateDocument(i);
        }
      }
    },
    async generateDocument(index) {
      this.allDocuments[index].additional.loading_button = true;
      Document = this.allDocuments[index];

      await this.$http
        .post(`/${this.resource}`, Document)
        .then((response) => {
          if (response.data.success) {
            Document.number = response.data.data.number_full.substring(5);
            this.allDocuments[index] == Document;
            this.$message.success(
              "Documento " + (Document.document_type_id == '01' ? 'FT' : 'BV') + '-' + response.data.data.number_full + " generado con éxito."
            );
          } else {
            this.$message.error(response.data.message);
          }
        })
        .catch((error) => {
          if (error.response.status === 422) {
            this.errors = error.response.data;
          } else {
            this.$message.error(error.response.data.message);
          }
        })
        .then(() => {
          this.allDocuments[index].additional.loading_button = false;
        });

      this.showGenerateAllDocuments();
    },
    showGenerateAllDocuments() {
      if (this.allDocuments.length > 0) {
        var emptyDocuments = 0;
        this.allDocuments.forEach((element) => {
          if (
            element.customer_id != null &&
            !element.additional.has_null_items &&
            element.number == "#"
          ) {
            emptyDocuments++;
          }
        });

        if (emptyDocuments > 0) {
          this.showGenerateAllDocumentsButton = true;
        } else {
          this.showGenerateAllDocumentsButton = false;
        }
      } else {
        this.showGenerateAllDocumentsButton = false;
      }
    },
  },
};
</script>