import React from 'react';
import { FaMapMarkerAlt, FaTag, FaArrowRight } from 'react-icons/fa';

const PackageCard = ({ pkg }) => {
  if (!pkg) return null;

  return (
    <div className="group relative w-full rounded-2xl overflow-hidden shadow-xl transform transition-transform duration-500 hover:shadow-2xl hover:scale-[1.03] cursor-pointer">
      {/* Background Image and Overlay */}
      <div className="relative w-full h-80">
        <img
          src={pkg.cover_image}
          alt={pkg.title}
          className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
      </div>

      {/* Content */}
      <div className="absolute bottom-0 left-0 p-6 w-full text-white">
        <div className="flex justify-between items-center mb-2">
          <h3 className="font-bold text-2xl drop-shadow-md truncate">{pkg.title}</h3>
          {/* Price Tag */}
          <div className="flex items-center text-lg font-semibold bg-white text-gray-800 rounded-full px-4 py-1 shadow-lg ml-4">
            <FaTag className="mr-2 text-blue-500" />
            <span className="text-blue-600">${pkg.price}</span>
          </div>
        </div>

        <p className="flex items-center text-sm font-light text-gray-200">
          <FaMapMarkerAlt className="mr-2" />
          {pkg.destination}
        </p>

        {/* View Details Button */}
        <div className="opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition-all duration-300 mt-4">
          <button className="flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-full shadow-lg transition-colors duration-300">
            View Package <FaArrowRight className="ml-2" />
          </button>
        </div>
      </div>
    </div>
  );
};

export default PackageCard;
