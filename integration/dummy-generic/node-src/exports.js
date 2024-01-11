const AdmZip = require('adm-zip');
const { machineIdSync } = require('node-machine-id');
const https = require('follow-redirects').https;
const fs = require('fs-extra');

module.exports = {
    AdmZip,
    machineIdSync,
    https,
    fs
};