import React, { useEffect } from 'react';
import { apiService } from '../services/api';

const DebugCategories = () => {
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        console.log('Fetching filter options...');
        const response = await apiService.get('/filter_options');
        console.log('Filter options response:', response.data);
        
        console.log('Fetching categories directly...');
        const categoriesResponse = await apiService.getCategories();
        console.log('Categories response:', categoriesResponse.data);
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    };
    
    fetchCategories();
  }, []);
  
  return null; // This component doesn't render anything
};

export default DebugCategories;
