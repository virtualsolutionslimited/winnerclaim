const dummyWinners = [
  {
    phone: "0548664851",
    name: "Joshua Koffie",
    email: "joshua.koffie@example.com",
    ghanaCard: "GHA-123456789-0",
    age: 28,
    verified: false,
    contractAccepted: false,
    kycCompleted: false,
  },
  {
    phone: "0201234567",
    name: "Ama Serwaa",
    email: "ama.serwaa@example.com",
    ghanaCard: "GHA-234567890-1",
    age: 32,
    verified: false,
    contractAccepted: false,
    kycCompleted: false,
  },
  {
    phone: "0247654321",
    name: "Kwame Mensah",
    email: "kwame.mensah@example.com",
    ghanaCard: "GHA-345678901-2",
    age: 22,
    verified: false,
    contractAccepted: false,
    kycCompleted: false,
  },
  {
    phone: "0551122334",
    name: "Esi Johnson",
    email: "esi.johnson@example.com",
    ghanaCard: "GHA-456789012-3",
    age: 17, // Under 18 for testing
    verified: false,
    contractAccepted: false,
    kycCompleted: false,
  },
  {
    phone: "0278899001",
    name: "Yaw Boateng",
    email: "yaw.boateng@example.com",
    ghanaCard: "GHA-567890123-4",
    age: 25,
    verified: false,
    contractAccepted: false,
    kycCompleted: false,
  },
  {
    phone: "0543322110",
    name: "Akosua Agyemang",
    email: "akosua.agyemang@example.com",
    ghanaCard: "GHA-678901234-5",
    age: 31,
    verified: false,
    contractAccepted: false,
    kycCompleted: false,
  },
  {
    phone: "0209876543",
    name: "Kofi Asante",
    email: "kofi.asante@example.com",
    ghanaCard: "GHA-789012345-6",
    age: 19,
    verified: false,
    contractAccepted: false,
    kycCompleted: false,
  },
  {
    phone: "0501122334",
    name: "Abena Owusu",
    email: "abena.owusu@example.com",
    ghanaCard: "GHA-890123456-7",
    age: 27,
    verified: false,
    contractAccepted: false,
    kycCompleted: false,
  },
];

// Function to find winner by phone number
function findWinnerByPhone(phone) {
  return dummyWinners.find((winner) => winner.phone === phone);
}

// Function to update winner data
function updateWinnerData(phone, updates) {
  const index = dummyWinners.findIndex((winner) => winner.phone === phone);
  if (index !== -1) {
    dummyWinners[index] = { ...dummyWinners[index], ...updates };
    return true;
  }
  return false;
}

export { dummyWinners, findWinnerByPhone, updateWinnerData };
