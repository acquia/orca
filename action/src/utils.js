const path = require('path');
function resolvePath(filepath) {
    if (filepath[0] === '~') {
        return path.join(process.env.HOME, filepath.slice(1));
    }
    return path.resolve(filepath)
}

module.exports = {
    resolvePath,
}
