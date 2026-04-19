const bcrypt = require('bcrypt');

function normalizePhpBcryptHash(hash) {
  if (typeof hash !== 'string') return '';
  if (hash.startsWith('$2y$')) {
    return `$2b$${hash.slice(4)}`;
  }
  return hash;
}

async function verifyPassword(plain, storedHash) {
  const normalizedHash = normalizePhpBcryptHash(storedHash);
  if (!normalizedHash) return false;
  return bcrypt.compare(plain, normalizedHash);
}

module.exports = {
  verifyPassword
};
